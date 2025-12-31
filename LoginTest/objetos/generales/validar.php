<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!function_exists('client_ip')) {
    function client_ip(): string {
        if (($_SERVER['REMOTE_ADDR'] ?? '') === '::1') {
            return '127.0.0.1';
        }
        $headers = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = explode(',', $_SERVER[$header])[0];
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        return '0.0.0.0';
    }
}

if (!function_exists('inspect_request_for_threats')) {
    function inspect_request_for_threats(mysqli $conn): bool {
        $ip = client_ip();
        $patterns = [
            '/(select|union|insert|update|delete|drop|--|\#|\*|;)/i',
            '/((\%27)|(\'))(\s*)|(\s+)(or|and)(\s+)((\%27)|(\'))(\s*)=|(\s*)=(\s*)((\%27)|(\'))/i'
        ];

        $inputs_to_check = ['GET' => $_GET, 'POST' => $_POST];
        if (isset($_SERVER['REQUEST_URI'])) {
            $inputs_to_check['REQUEST_URI'] = ['uri' => $_SERVER['REQUEST_URI']];
        }

        foreach ($inputs_to_check as $source_name => $input_data) {
            foreach ($input_data as $key => $value) {
                $strings_to_check = [$key, $value];
                foreach ($strings_to_check as $string_to_check) {
                    if (!is_string($string_to_check)) continue;
                    foreach ($patterns as $pattern) {
                        if (preg_match($pattern, $string_to_check)) {
                            // AMENAZA DETECTADA
                            $stmt_alert = $conn->prepare('INSERT INTO tbl_security_alerts (source_ip, input_source, input_key, malicious_payload) VALUES (?, ?, ?, ?)');
                            if ($stmt_alert) {
                                // Construir payload legible: Variable y Valor
                                $valorPlano = '';
                                if (is_array($value)) {
                                    $valorPlano = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                                } elseif (is_scalar($value)) {
                                    $valorPlano = (string)$value;
                                }
                                // Truncar por seguridad para evitar payloads enormes
                                if (function_exists('mb_strlen') ? mb_strlen($valorPlano, 'UTF-8') > 500 : strlen($valorPlano) > 500) {
                                    $valorPlano = (function_exists('mb_substr') ? mb_substr($valorPlano, 0, 500, 'UTF-8') : substr($valorPlano, 0, 500)) . '…';
                                }
                                $payloadDetallado = 'Variable: ' . $key . ' | Valor: ' . $valorPlano;
                                $stmt_alert->bind_param('ssss', $ip, $source_name, $key, $payloadDetallado);
                                $stmt_alert->execute();
                                $stmt_alert->close();
                            }
                            return true; // Amenaza encontrada
                        }
                    }
                }
            }
        }
        return false; // No se encontró amenaza
    }
}

if (!function_exists('is_entity_blocked')) {
    /**
     * Verifica si una entidad está bloqueada y devuelve el motivo del bloqueo.
     *
     * @param mysqli $conn La conexión a la base de datos.
     * @param string $type El tipo de entidad ('ip', 'user').
     * @param string $identifier El identificador de la entidad.
     * @return string|false El motivo del bloqueo si está bloqueada, o false si no lo está.
     */
    function is_entity_blocked(mysqli $conn, string $type, string $identifier) {
        if (empty($identifier)) return false;
        
        $stmt = $conn->prepare('SELECT block_reason FROM tbl_blocked_entities WHERE entity_type = ? AND entity_identifier = ? AND block_expires_at > NOW() LIMIT 1');
        $stmt->bind_param('ss', $type, $identifier);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $stmt->bind_result($reason);
            $stmt->fetch();
            $stmt->close();
            return $reason;
        }
        
        $stmt->close();
        return false;
    }
}

if (!function_exists('enforce_ip_security')) {
    /**
     * Verifica si la IP del cliente está bloqueada. Si lo está, destruye la sesión
     * y devuelve un mensaje de error. En páginas que no son index.php, redirige.
     *
     * @return string Retorna un mensaje de error si la IP está bloqueada, o una cadena vacía si no lo está.
     */
    function enforce_ip_security(): string {
        require_once __DIR__ . '/../../configuraciones/conexion_bd.php';
        $conn = mysqli_connect_db();
        $ip = client_ip();
        $error_message = '';

        $block_reason = is_entity_blocked($conn, 'ip', $ip);
        if ($block_reason) {
            if (session_status() != PHP_SESSION_NONE) {
                session_unset();
                session_destroy();
            }

            switch ($block_reason) {
                case 'security':
                    $error_message = 'Acceso denegado por actividad sospechosa. Cuenta bloqueada.';
                    break;
                case 'failed_attempt':
                    $error_message = 'Acceso denegado por demasiados intentos fallidos. Cuenta bloqueada.';
                    break;
                default:
                    $error_message = 'Acceso denegado. Cuenta bloqueada por motivos de seguridad.';
                    break;
            }

            if (basename($_SERVER['SCRIPT_NAME']) !== 'index.php') {
                header('Location: /index.php?error=bloqueado_ip');
                exit();
            }
        }

        $conn->close();
        return $error_message;
    }
}

if (!function_exists('upsert_block')) {
    function upsert_block(mysqli $conn, string $type, string $identifier, string $reason = 'security'): void {
        // Inserta o actualiza un bloqueo para que expire en 5 años.
        $sql = "INSERT INTO tbl_blocked_entities (entity_type, entity_identifier, block_expires_at, created_at, block_reason) VALUES (?, ?, NOW() + INTERVAL 5 YEAR, NOW(), ?) ON DUPLICATE KEY UPDATE block_expires_at = NOW() + INTERVAL 5 YEAR, created_at = VALUES(created_at), block_reason = VALUES(block_reason)";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            // Manejar error de preparación, si es necesario
            return;
        }
        $stmt->bind_param('sss', $type, $identifier, $reason);
        $stmt->execute();
        $stmt->close();
    }
}

if (!function_exists('log_failed_attempt')) {
    function log_failed_attempt(mysqli $conn, ?int $user_id, string $ip): void {
        $stmt = $conn->prepare('INSERT INTO tbl_login_attempts (user_id, source_ip) VALUES (?, ?)');
        $stmt->bind_param('is', $user_id, $ip);
        $stmt->execute();
        $stmt->close();
    }
}

if (!function_exists('count_recent_failed_attempts_by_ip')) {
    function count_recent_failed_attempts_by_ip(mysqli $conn, string $ip, int $minutes): int {
        $sql = "SELECT COUNT(*) FROM tbl_login_attempts WHERE source_ip = ? AND attempt_time > NOW() - INTERVAL ? MINUTE";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return 0;
        }
        $stmt->bind_param('si', $ip, $minutes);
        $stmt->execute();
        $count = 0;
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();
        return $count;
    }
}

if (!function_exists('sanitizar_input')) {
    /**
     * Sanea una cadena de texto para su uso general.
     *
     * Esta función realiza una limpieza básica, como eliminar espacios en blanco al inicio y al final.
     * Es un buen primer paso antes de usar los datos en una consulta preparada o mostrarlos en pantalla.
     * La protección contra inyección SQL la proveen las consultas preparadas, no esta función.
     *
     * @param string|null $input La cadena a sanear.
     * @return string La cadena saneada.
     */
    function sanitizar_input(?string $input): string {
        if ($input === null) {
            return '';
        }
        return trim($input);
    }
}

if (!function_exists('sanitize_for_db')) {
    /**
     * Limpia una cadena para que sea segura para usar en una consulta de base de datos.
     * NOTA: El uso de sentencias preparadas (prepared statements) es SIEMPRE preferible.
     * Esta función es un fallback o para casos donde las sentencias preparadas no son viables.
     *
     * @param mysqli $conn La conexión a la base de datos.
     * @param string $input La cadena a limpiar.
     * @return string La cadena limpia.
     */
    function sanitize_for_db(mysqli $conn, string $input): string {
        return $conn->real_escape_string($input);
    }
}

if (!function_exists('process_login_attempt')) {
    function process_login_attempt(mysqli $conn): void {
        $ip = client_ip();
        $username = strtolower(trim($_POST['username'] ?? ''));
        $password = $_POST['password'] ?? '';

        inspect_request_for_threats($conn);

        // 1. VERIFICACIÓN CRÍTICA: COMPROBAR SI LA IP ESTÁ BLOQUEADA
        if (is_entity_blocked($conn, 'ip', $ip)) {
            session_write_close();
            header('Location: index.php?error=bloqueado_ip');
            exit();
        }

        // Guardar el usuario intentado para repoblar el formulario
        $_SESSION['last_attempted_user'] = $username;

        // 2. PROCESO DE LOGIN
        $stmt = $conn->prepare("SELECT id, password, is_permanently_blocked FROM tbl_usuarios WHERE username = ?");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $stmt->store_result();
        $user = null;
        if ($stmt->num_rows > 0) {
            $stmt->bind_result($id, $password_hash, $is_permanently_blocked);
            $stmt->fetch();
            $user = ['id' => $id, 'password' => $password_hash, 'is_permanently_blocked' => $is_permanently_blocked];
        }
        $stmt->close();

        if ($user && $user['is_permanently_blocked']) {
            session_write_close();
            header('Location: index.php?error=bloqueado_permanente');
            exit();
        }

        if ($user && password_verify($password, $user['password'])) {
            unset($_SESSION['last_attempted_user'], $_SESSION['login_error']);
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['username'] = $username;
            session_write_close();
            $host = $_SERVER['HTTP_HOST'];
            $uri = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
            header("Location: http://$host$uri/principal.php");
            exit();
        }

        // 3. MANEJO DE INTENTOS FALLIDOS
        $user_id = $user['id'] ?? null;
        log_failed_attempt($conn, $user_id, $ip);
        $ip_attempts = count_recent_failed_attempts_by_ip($conn, $ip, ATTEMPT_WINDOW_MINUTES);

        if ($ip_attempts >= MAX_IP_ATTEMPTS) {
            upsert_block($conn, 'ip', $ip, 'failed_attempt');
            session_write_close();
            header('Location: index.php?error=bloqueado_ip');
            exit();
        }

        session_write_close();
        header('Location: index.php?error=credenciales');
        exit();
    }
}
