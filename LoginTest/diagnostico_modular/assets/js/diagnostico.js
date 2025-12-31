/**
 * Lógica JavaScript para el módulo de diagnóstico
 * Manejo de modales, filtros y detalles forenses
 */

/**
 * Confirma desbloqueo de IP
 */
function confirmUnblock(ip) {
    return confirm(`¿Está seguro de que quiere desbloquear la IP ${ip}?`);
}

/**
 * Escapa HTML para prevenir XSS
 */
function escapeHtml(str) {
    const div = document.createElement('div');
    div.appendChild(document.createTextNode(str ?? ''));
    return div.innerHTML;
}

/**
 * Extrae variable y valor de una alerta
 */
function extraerVarValor(alerta) {
    const payload = String((alerta && alerta.payload) || '');
    const key = String((alerta && alerta.key) || '');
    let variable = '';
    
    // Identificar variable
    if (key && !/LOGIN_|IP_BLOQUEADA|ATAQUE|DETECT|SUSP|SECUR/i.test(key)) {
        variable = key;
    }
    
    let match = payload.match(/(?:Variable|Campo|Par[áa]metro|Input|Clave)\s*:\s*([^|\r\n]+)/i);
    if (!variable && match) {
        variable = match[1].trim();
    }
    
    // Identificar valor
    let valor = '';
    match = payload.match(/(?:Valor(?:\s*sospechoso)?|Value)\s*:\s*([^|\r\n]+)/i);
    if (match) {
        valor = match[1].trim();
    }
    
    if (!valor) {
        const userMatch = payload.match(/Usuario\s*:\s*([^|\r\n]+)/i);
        if (userMatch) {
            if (!variable) variable = 'usuario';
            valor = userMatch[1].trim();
        }
    }
    
    if (!valor && /POST|GET/i.test(String(alerta?.source||'')) && /pass|password|contraseñ|contrasen/i.test(key)) {
        valor = '(no almacenado por seguridad)';
    }
    
    return { variable, valor };
}

/**
 * Abre modal con detalle forense de una IP
 */
async function abrirDetalle(ip, soloMotivo = false, desdeArg = null, hastaArg = null) {
    const modal = document.getElementById('detailModal');
    const contenido = document.getElementById('detailContent');
    
    contenido.innerHTML = 'Cargando...';
    modal.style.display = 'flex';
    
    // Construir URL de la API
    const params = new URLSearchParams();
    params.set('ip', ip);
    
    const inputDesde = document.getElementById('start_date');
    const inputHasta = document.getElementById('end_date');
    
    if (desdeArg) {
        params.set('desde', desdeArg);
    } else if (inputDesde && inputDesde.value) {
        params.set('desde', inputDesde.value);
    }
    
    if (hastaArg) {
        params.set('hasta', hastaArg);
    } else if (inputHasta && inputHasta.value) {
        params.set('hasta', inputHasta.value);
    }
    
    // Llamar a la API
    let datos = null;
    try {
        const url = `diagnostico_modular/api/detalle_forense.php?${params.toString()}`;
        const resp = await fetch(url, { credentials: 'same-origin' });
        if (resp.ok) {
            const json = await resp.json();
            if (json && !json.error) {
                datos = json;
            }
        }
    } catch (error) {
        console.error('Error al cargar detalle:', error);
    }
    
    if (!datos) {
        contenido.innerHTML = '<div class="alert alert-danger">Error al cargar el detalle.</div>';
        return;
    }
    
    // Renderizar datos
    const resumen = datos.resumen || {};
    const intentos = datos.intentos_fallidos || {};
    const porUsuario = intentos.por_usuario || [];
    const detalle = intentos.detalle || [];
    const alertas = datos.alertas || [];
    const historial = datos.historial_bloqueos || [];
    const entrada = datos.entrada || null;
    
    if (soloMotivo) {
        renderizarSoloMotivo(datos, resumen, porUsuario, alertas, entrada);
    } else {
        renderizarDetalleCompleto(datos, resumen, intentos, porUsuario, detalle, alertas, historial);
    }
}

/**
 * Renderiza solo el motivo del bloqueo actual
 */
function renderizarSoloMotivo(datos, resumen, porUsuario, alertas, entrada) {
    const contenido = document.getElementById('detailContent');
    let html = '';
    
    html += '<div class="section"><h3>Bloqueo actual</h3>' +
            '<div><strong>IP:</strong> ' + escapeHtml(datos.ip || '') + '</div>' +
            '<div><strong>Motivo actual:</strong> ' + escapeHtml(resumen.motivo_actual || 'N/D') + '</div>' +
            '</div>';
    
    const motivo = String(resumen.motivo_actual || '').toLowerCase();
    
    if (motivo.includes('sospech')) {
        // Buscar última alerta relevante
        let alertaRelevante = null;
        let eventoEntrada = entrada || null;
        
        if (alertas && alertas.length) {
            const ordenadas = [...alertas].sort((a,b) => String(b.hora||'').localeCompare(String(a.hora||'')));
            const horaBloq = String(resumen.bloqueado_desde||'');
            
            const esGenerica = (x) => /BLOQUE|BLOCK|ATAQUE|DETECT|SUSP|SECUR/i.test(String(x.key||'')) || /LOGIN_EXITOSO/i.test(String(x.key||''));
            const esInput = (x) => /POST|GET/i.test(String(x.source||'')) && !esGenerica(x);
            
            alertaRelevante = ordenadas.find(x => /BLOQUE|BLOCK|ATAQUE|DETECT|SUSP/i.test(String(x.key||''))) || ordenadas[0];
            
            if (!eventoEntrada) {
                const baseHora = alertaRelevante ? String(alertaRelevante.hora||'') : horaBloq;
                eventoEntrada = ordenadas.find(x => esInput(x) && (!baseHora || String(x.hora||'') <= baseHora)) || null;
            }
        }
        
        const srcEvt = eventoEntrada || alertaRelevante;
        const vv = srcEvt ? extraerVarValor(srcEvt) : {variable:'', valor:''};
        
        if ((!vv.variable || vv.variable === '') && srcEvt && /POST|GET/i.test(String(srcEvt.source||''))) {
            vv.variable = String(srcEvt.key || '');
        }
        
        const payloadMostrar = srcEvt ? srcEvt.payload : (alertaRelevante ? alertaRelevante.payload : '');
        
        html += '<div class="section"><h3>Última alerta</h3>';
        if (alertaRelevante) {
            html += '<table class="subtable"><thead><tr><th>Hora</th><th>Clave</th><th>Variable</th><th>Valor</th><th>Payload</th></tr></thead><tbody>' +
                    '<tr><td>' + escapeHtml((alertaRelevante.hora||srcEvt?.hora)||'') + '</td>' +
                    '<td>' + escapeHtml(alertaRelevante.key||'') + '</td>' +
                    '<td>' + escapeHtml(vv.variable||'') + '</td>' +
                    '<td><code>' + escapeHtml(vv.valor||'') + '</code></td>' +
                    '<td><code>' + escapeHtml(payloadMostrar||'') + '</code></td></tr>' +
                    '</tbody></table>';
        } else {
            html += '<div>No hay alertas registradas para este bloqueo.</div>';
        }
        html += '</div>';
        
    } else if (motivo.includes('fallid')) {
        html += '<div class="section"><h3>Cantidad de intentos fallidos por usuario:</h3>' +
                '<table class="subtable"><thead><tr><th>Usuario</th><th>Cantidad</th><th>Última hora</th></tr></thead><tbody>';
        
        if (porUsuario.length > 0) {
            porUsuario.forEach(u => {
                html += '<tr><td>' + escapeHtml(u.user||'') + '</td>' +
                        '<td>' + escapeHtml(String(u.cantidad||0)) + '</td>' +
                        '<td>' + escapeHtml(u.ult_hora||'') + '</td></tr>';
            });
        } else {
            html += '<tr><td colspan="3">Sin registros en el rango.</td></tr>';
        }
        
        html += '</tbody></table></div>';
    }
    
    contenido.innerHTML = html;
}

/**
 * Renderiza detalle completo del bloqueo con diseño moderno
 */
function renderizarDetalleCompleto(datos, resumen, intentos, porUsuario, detalle, alertas, historial) {
    const contenido = document.getElementById('detailContent');
    let html = '';
    
    // Card de Resumen con gradiente
    html += '<div class="summary-card">' +
            '<h3>📊 Resumen del Bloqueo</h3>' +
            '<div class="summary-grid">' +
            '<div class="summary-item">IP Bloqueada: ' + escapeHtml(datos.ip || 'N/D') + '</div>' +
            '<div class="summary-item">Motivo: ' + escapeHtml(resumen.motivo_actual || 'N/D') + '</div>' +
            '<div class="summary-item">Bloqueado desde: ' + escapeHtml(resumen.bloqueado_desde || 'N/D') + '</div>' +
            '<div class="summary-item">Expira: ' + escapeHtml(resumen.expira || 'N/D') + '</div>' +
            '</div></div>';
    
    // Stats Cards
    html += '<div class="stats-container">' +
            '<div class="stat-card">' +
            '<div class="stat-label">🚫 Total de Intentos</div>' +
            '<div class="stat-value">' + escapeHtml(String(intentos.total || 0)) + '</div>' +
            '</div>' +
            '<div class="stat-card">' +
            '<div class="stat-label">👤 Usuarios Afectados</div>' +
            '<div class="stat-value">' + porUsuario.length + '</div>' +
            '</div>' +
            '</div>';
    
    // Intentos por usuario
    if (porUsuario.length > 0) {
        html += '<div class="section"><h3>👥 Intentos por Usuario</h3>' +
                '<table class="subtable modern-table"><thead><tr><th>Usuario</th><th>Cantidad</th><th>Última Hora</th></tr></thead><tbody>';
        
        porUsuario.forEach(u => {
            const badgeClass = u.cantidad > 5 ? 'badge-danger' : (u.cantidad > 2 ? 'badge-warning' : 'badge-info');
            html += '<tr><td><strong>' + escapeHtml(u.user||'') + '</strong></td>' +
                    '<td><span class="badge ' + badgeClass + '">' + escapeHtml(String(u.cantidad||0)) + ' intentos</span></td>' +
                    '<td>' + escapeHtml(u.ult_hora||'') + '</td></tr>';
        });
        
        html += '</tbody></table></div>';
    }
    
    // Timeline de eventos
    if (detalle.length > 0) {
        html += '<div class="section"><h3>⏱️ Cronología de Eventos (Último Minuto)</h3><div style="margin-top:16px;">';
        
        detalle.forEach(d => {
            const tipoIcon = d.tipo && d.tipo.includes('bloqueada') ? '🔒' : '❌';
            html += '<div class="timeline-item">' +
                    '<div class="timeline-time">' + escapeHtml(d.hora||'') + '</div>' +
                    '<div class="timeline-content">' +
                    '<strong>' + tipoIcon + ' ' + escapeHtml(d.tipo || 'Evento') + '</strong> | ' +
                    'Usuario: <code>' + escapeHtml(d.usuario||'') + '</code>' +
                    '</div></div>';
        });
        
        html += '</div></div>';
    }
    
    // Alertas (si hay)
    if (alertas.length > 0) {
        html += '<div class="section"><h3>⚠️ Alertas de Seguridad</h3>' +
                '<table class="subtable modern-table"><thead><tr><th>Hora</th><th>Tipo</th><th>Detalles</th></tr></thead><tbody>';
        
        alertas.forEach(a => {
            html += '<tr><td>' + escapeHtml(a.hora||'') + '</td>' +
                    '<td><span class="badge badge-warning">' + escapeHtml(a.key||'') + '</span></td>' +
                    '<td><code style="font-size:0.85rem;">' + escapeHtml(a.payload||'') + '</code></td></tr>';
        });
        
        html += '</tbody></table></div>';
    }
    
    // Historial (si hay)
    if (historial.length > 0) {
        html += '<div class="section"><h3>📜 Historial de Bloqueos</h3>' +
                '<table class="subtable modern-table"><thead><tr><th>Motivo</th><th>IP</th><th>Bloqueado</th><th>Desbloqueado</th></tr></thead><tbody>';
        
        historial.forEach(h => {
            html += '<tr><td>' + escapeHtml(h.razon||'') + '</td>' +
                    '<td><code>' + escapeHtml(h.ip||'') + '</code></td>' +
                    '<td>' + escapeHtml(h.bloqueado||'') + '</td>' +
                    '<td>' + escapeHtml(h.desbloqueado||'') + '</td></tr>';
        });
        
        html += '</tbody></table></div>';
    }
    
    contenido.innerHTML = html;
}

/**
 * Cierra el modal de detalle
 */
function cerrarDetalle() {
    const modal = document.getElementById('detailModal');
    modal.style.display = 'none';
}
