(() => {
    const root = document.querySelector('[data-asistente]');
    if (!root || !window.THAKI_ASISTENTE) return;
    if (!window.L) {
        console.error('Leaflet no cargó');
        return;
    }

    const lugares = window.THAKI_ASISTENTE.lugares || [];
    const destinoInput = root.querySelector('[data-ruta-buscar]');
    const destinosHidden = root.querySelector('[data-ruta-destinos-hidden]');
    const tipoHidden = root.querySelector('[data-ruta-tipo-hidden]');
    const ideaHidden = root.querySelector('[data-ruta-idea-hidden]');
    const nombreHidden = root.querySelector('[data-ruta-nombre-hidden]');
    const distanciaHidden = root.querySelector('[data-ruta-distancia-hidden]');
    const desnivelHidden = root.querySelector('[data-ruta-desnivel-hidden]');
    const paradasHidden = root.querySelector('[data-ruta-paradas-hidden]');
    const actividad = root.querySelector('[data-ruta-actividad]');
    const actividadBtn = root.querySelector('[data-ruta-actividad-btn]');
    const actividadMenu = root.querySelector('[data-ruta-actividad-menu]');
    const actividadLabel = root.querySelector('[data-ruta-actividad-label]');
    const actividadIco = root.querySelector('[data-ruta-actividad-ico]');
    const agregarSelect = root.querySelector('[data-ruta-agregar]');
    const ficha = root.querySelector('[data-asistente-ficha]');
    const foto = root.querySelector('[data-asistente-foto]');
    const region = root.querySelector('[data-asistente-region]');
    const nombre = root.querySelector('[data-asistente-nombre]');
    const texto = root.querySelector('[data-asistente-texto]');
    const enlace = root.querySelector('[data-asistente-enlace]');
    const elegir = root.querySelector('[data-asistente-elegir]');
    const listaPuntos = root.querySelector('[data-ruta-puntos]');
    const distEl = root.querySelector('[data-ruta-distancia]');
    const distMapaEl = root.querySelector('[data-ruta-distancia-mapa]');
    const desEl = root.querySelector('[data-ruta-desnivel]');
    const desMapaEl = root.querySelector('[data-ruta-desnivel-mapa]');
    const tiempoEl = root.querySelector('[data-ruta-tiempo]');
    const paradasEl = root.querySelector('[data-ruta-paradas]');
    const hint = root.querySelector('[data-ruta-hint]');
    const perfilLinea = root.querySelector('[data-ruta-perfil-linea]');
    const perfilInicio = root.querySelector('[data-ruta-perfil-inicio]');
    const perfilFin = root.querySelector('[data-ruta-perfil-fin]');
    const guardarBtn = root.querySelector('[data-ruta-guardar]');
    const panel = root.querySelector('[data-ruta-panel]');
    const panelPuntos = root.querySelector('[data-ruta-panel-puntos]');
    const panelLugares = root.querySelector('[data-ruta-panel-lugares]');
    const mapaNodo = document.getElementById('asistente-mapa');
    const marcadoresLugar = {};
    const waypoints = [];
    let polyline = null;
    let seleccionado = window.THAKI_ASISTENTE.activo || null;
    let pendiente = null;

    const velocidadKmH = {
        Senderismo: 4.2,
        MTB: 14,
        'Trail running': 8.5,
        Correr: 9.5,
        'Ciclismo de carretera': 22,
        Mochilear: 3.4,
        Trekking: 3.8,
        'Viaje a medida': 5
    };

    const fmtKm = (km) => km.toLocaleString('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' km';
    const fmtM = (m) => Math.round(m).toLocaleString('es-BO') + ' m';
    const fmtTiempo = (horas) => {
        if (!horas || horas < 0.1) return '0 h';
        if (horas >= 24) return '24 h+';
        const h = Math.floor(horas);
        const m = Math.round((horas - h) * 60);
        if (h === 0) return `${m} min`;
        if (m === 0) return `${h} h`;
        return `${h} h ${m} min`;
    };

    const haversineKm = (a, b) => {
        const R = 6371;
        const toRad = (d) => (d * Math.PI) / 180;
        const dLat = toRad(b.lat - a.lat);
        const dLng = toRad(b.lng - a.lng);
        const lat1 = toRad(a.lat);
        const lat2 = toRad(b.lat);
        const h = Math.sin(dLat / 2) ** 2 + Math.cos(lat1) * Math.cos(lat2) * Math.sin(dLng / 2) ** 2;
        return 2 * R * Math.asin(Math.sqrt(h));
    };

    const mapa = L.map(mapaNodo, { zoomControl: false, scrollWheelZoom: true }).setView([-16.5, -65.5], 5.5);
    L.control.zoom({ position: 'bottomright' }).addTo(mapa);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 18,
        attribution: '&copy; OpenStreetMap'
    }).addTo(mapa);

    const iconoLugar = (lugar, activo) => L.divIcon({
        className: '',
        html: `<div class="asistente-marcador${activo ? ' activo' : ''}"><img src="${lugar.imagen}" alt="${lugar.nombre}"></div>`,
        iconSize: [46, 46],
        iconAnchor: [23, 23],
        popupAnchor: [0, -24]
    });

    const iconoWaypoint = (n, total) => {
        const clase = n === 1 ? ' inicio' : (n === total ? ' fin' : '');
        return L.divIcon({
            className: '',
            html: `<div class="ruta-waypoint${clase}">${n}</div>`,
            iconSize: [26, 26],
            iconAnchor: [13, 13]
        });
    };

    const popupHtml = (lugar) => `
        <div class="asistente-popup">
            <img src="${lugar.imagen}" alt="${lugar.nombre}">
            <div>
                <span>${lugar.region}</span>
                <strong>${lugar.nombre}</strong>
                <p>${(lugar.descripcion || '').slice(0, 110)}${(lugar.descripcion || '').length > 110 ? '…' : ''}</p>
                <a href="${lugar.url}">Ver detalle ↗</a>
            </div>
        </div>`;

    const syncForm = (dist = 0, desnivel = 0) => {
        const nombres = waypoints.map((p) => p.nombre).filter(Boolean);
        const titulo = root.querySelector('[data-ruta-nombre]')?.textContent?.trim() || 'Ruta personalizada';
        if (destinosHidden) destinosHidden.value = nombres.join(' · ');
        if (tipoHidden && actividad) tipoHidden.value = actividad.value;
        if (nombreHidden) nombreHidden.value = titulo;
        if (ideaHidden) ideaHidden.value = '';
        if (distanciaHidden) distanciaHidden.value = dist.toFixed(2);
        if (desnivelHidden) desnivelHidden.value = Math.round(desnivel);
        if (paradasHidden) paradasHidden.value = String(waypoints.length);
        if (guardarBtn) guardarBtn.disabled = waypoints.length < 2;
    };

    const actualizarPerfil = (dist) => {
        if (!perfilLinea) return;
        if (waypoints.length < 2) {
            perfilLinea.setAttribute('d', 'M0 60 L320 60');
            if (perfilInicio) perfilInicio.textContent = '0,0 km';
            if (perfilFin) perfilFin.textContent = '0,0 km';
            return;
        }
        const puntos = [];
        let acum = 0;
        let elev = 42;
        puntos.push([0, elev]);
        for (let i = 1; i < waypoints.length; i += 1) {
            acum += haversineKm(waypoints[i - 1], waypoints[i]);
            const x = dist > 0 ? (acum / dist) * 320 : 0;
            elev = 18 + Math.abs(Math.sin(i * 1.35) * 34) + ((waypoints[i].lat + 20) % 7);
            puntos.push([x, Math.max(8, Math.min(64, elev))]);
        }
        const d = puntos.map((p, i) => `${i === 0 ? 'M' : 'L'}${p[0].toFixed(1)} ${p[1].toFixed(1)}`).join(' ');
        perfilLinea.setAttribute('d', d);
        if (perfilInicio) perfilInicio.textContent = '0,0 km';
        if (perfilFin) perfilFin.textContent = fmtKm(dist);
    };

    const actualizarStats = () => {
        let dist = 0;
        for (let i = 1; i < waypoints.length; i += 1) {
            dist += haversineKm(waypoints[i - 1], waypoints[i]);
        }
        const desnivel = waypoints.length > 1 ? dist * 28 : 0;
        const actividadValor = actividad?.value || 'Senderismo';
        const vel = velocidadKmH[actividadValor] || 5;
        const horas = dist > 0 ? dist / vel : 0;
        if (distEl) distEl.textContent = fmtKm(dist);
        if (distMapaEl) distMapaEl.textContent = fmtKm(dist);
        if (paradasEl) paradasEl.textContent = String(waypoints.length);
        if (desEl) desEl.textContent = fmtM(desnivel);
        if (desMapaEl) desMapaEl.textContent = fmtM(desnivel);
        if (tiempoEl) tiempoEl.textContent = fmtTiempo(horas);
        if (hint) hint.hidden = waypoints.length > 0;
        actualizarPerfil(dist);
        syncForm(dist, desnivel);
    };

    const renderPuntos = () => {
        if (!listaPuntos) return;
        if (!waypoints.length) {
            listaPuntos.innerHTML = '<li class="ruta-puntos-vacio" data-ruta-vacio>Todavía no hay paradas. Elige un lugar registrado o haz clic en el mapa.</li>';
            return;
        }
        listaPuntos.innerHTML = waypoints.map((p, i) => `
            <li data-idx="${i}">
                <span class="ruta-punto-n">${i + 1}</span>
                <div>
                    <strong>${p.nombre || `Punto ${i + 1}`}</strong>
                    <small>${p.region || `${p.lat.toFixed(3)}, ${p.lng.toFixed(3)}`}</small>
                </div>
                <button type="button" data-quitar="${i}" aria-label="Quitar punto">×</button>
            </li>`).join('');
    };

    const dibujarRuta = () => {
        if (polyline) {
            mapa.removeLayer(polyline);
            polyline = null;
        }
        waypoints.forEach((p) => {
            if (p.marker) mapa.removeLayer(p.marker);
        });
        if (!waypoints.length) {
            renderPuntos();
            actualizarStats();
            return;
        }
        const coords = waypoints.map((p) => [p.lat, p.lng]);
        polyline = L.polyline(coords, {
            color: '#2f8f4e',
            weight: 5,
            opacity: 0.92,
            lineJoin: 'round',
            lineCap: 'round'
        }).addTo(mapa);
        waypoints.forEach((p, i) => {
            p.marker = L.marker([p.lat, p.lng], {
                icon: iconoWaypoint(i + 1, waypoints.length),
                zIndexOffset: 600
            }).addTo(mapa);
        });
        if (coords.length === 1) mapa.flyTo(coords[0], Math.max(mapa.getZoom(), 7), { duration: 0.5 });
        else mapa.fitBounds(polyline.getBounds(), { padding: [60, 60], maxZoom: 8 });
        renderPuntos();
        actualizarStats();
    };

    const agregarPunto = (punto) => {
        const existe = waypoints.some((p) => p.slug && punto.slug && p.slug === punto.slug);
        if (existe) return;
        waypoints.push({
            ...punto,
            marker: null
        });
        dibujarRuta();
    };

    const agregarLugar = (slug) => {
        const lugar = lugares.find((item) => item.slug === slug);
        if (!lugar) return;
        agregarPunto({
            slug: lugar.slug,
            nombre: lugar.nombre,
            region: lugar.region,
            imagen: lugar.imagen,
            lat: lugar.lat,
            lng: lugar.lng
        });
        activar(slug, true);
    };

    const quitarPunto = (idx) => {
        const [quitado] = waypoints.splice(idx, 1);
        if (quitado?.marker) mapa.removeLayer(quitado.marker);
        dibujarRuta();
    };

    const mostrarFicha = (lugar) => {
        if (!ficha) return;
        ficha.hidden = false;
        foto.src = lugar.imagen;
        foto.alt = lugar.nombre;
        region.textContent = lugar.region;
        nombre.textContent = lugar.nombre;
        texto.textContent = lugar.descripcion || '';
        enlace.href = lugar.url;
        elegir.dataset.slug = lugar.slug;
        pendiente = lugar.slug;
    };

    const activar = (slug, centrar = true) => {
        seleccionado = slug;
        Object.entries(marcadoresLugar).forEach(([clave, marker]) => {
            const lugar = lugares.find((item) => item.slug === clave);
            if (!lugar) return;
            marker.setIcon(iconoLugar(lugar, clave === slug));
        });
        const lugar = lugares.find((item) => item.slug === slug);
        if (!lugar) return;
        mostrarFicha(lugar);
        if (centrar) mapa.flyTo([lugar.lat, lugar.lng], Math.max(mapa.getZoom(), 7), { duration: 0.55 });
    };

    const grupo = [];
    lugares.forEach((lugar) => {
        const marker = L.marker([lugar.lat, lugar.lng], {
            icon: iconoLugar(lugar, lugar.slug === seleccionado),
            zIndexOffset: 200
        }).addTo(mapa).bindPopup(popupHtml(lugar), { maxWidth: 240 });
        marker.on('click', () => {
            activar(lugar.slug, false);
            agregarLugar(lugar.slug);
        });
        marcadoresLugar[lugar.slug] = marker;
        grupo.push([lugar.lat, lugar.lng]);
    });
    if (grupo.length) mapa.fitBounds(grupo, { padding: [48, 48], maxZoom: 7 });
    if (seleccionado) activar(seleccionado, true);

    root.querySelectorAll('[data-asistente-chip]').forEach((chip) => {
        chip.addEventListener('click', () => agregarLugar(chip.dataset.slug));
    });

    elegir?.addEventListener('click', () => {
        if (elegir.dataset.slug) agregarLugar(elegir.dataset.slug);
    });

    agregarSelect?.addEventListener('change', () => {
        if (!agregarSelect.value) return;
        agregarLugar(agregarSelect.value);
        agregarSelect.value = '';
    });

    listaPuntos?.addEventListener('click', (event) => {
        const btn = event.target.closest('[data-quitar]');
        if (!btn) return;
        quitarPunto(Number(btn.dataset.quitar));
    });

    mapa.on('click', (event) => {
        if (event.originalEvent?.target?.closest('.asistente-marcador, .ruta-waypoint, .leaflet-popup')) return;
        const cercano = lugares
            .map((lugar) => ({ lugar, d: haversineKm({ lat: event.latlng.lat, lng: event.latlng.lng }, lugar) }))
            .sort((a, b) => a.d - b.d)[0];
        if (cercano && cercano.d < 40) {
            agregarLugar(cercano.lugar.slug);
            return;
        }
        agregarPunto({
            nombre: `Punto ${waypoints.length + 1}`,
            region: `${event.latlng.lat.toFixed(3)}, ${event.latlng.lng.toFixed(3)}`,
            lat: event.latlng.lat,
            lng: event.latlng.lng
        });
    });

    destinoInput?.addEventListener('change', () => {
        const valor = (destinoInput.value || '').toLowerCase();
        const match = lugares.find((lugar) => lugar.nombre.toLowerCase().includes(valor) || valor.includes(lugar.nombre.toLowerCase()));
        if (match) {
            agregarLugar(match.slug);
            destinoInput.value = match.nombre;
        }
    });
    root.querySelector('[data-ruta-buscar-form]')?.addEventListener('submit', (event) => {
        event.preventDefault();
        destinoInput?.dispatchEvent(new Event('change'));
    });

    root.querySelector('[data-ruta-deshacer]')?.addEventListener('click', () => {
        if (waypoints.length) quitarPunto(waypoints.length - 1);
    });
    root.querySelector('[data-ruta-limpiar]')?.addEventListener('click', () => {
        while (waypoints.length) quitarPunto(waypoints.length - 1);
    });
    root.querySelector('[data-ruta-centrar]')?.addEventListener('click', () => {
        if (polyline) mapa.fitBounds(polyline.getBounds(), { padding: [60, 60], maxZoom: 8 });
        else if (grupo.length) mapa.fitBounds(grupo, { padding: [48, 48], maxZoom: 7 });
    });

    root.querySelectorAll('[data-ruta-tab]').forEach((tab) => {
        tab.addEventListener('click', () => {
            root.querySelectorAll('[data-ruta-tab]').forEach((t) => {
                const activo = t === tab;
                t.classList.toggle('activo', activo);
                t.setAttribute('aria-selected', activo ? 'true' : 'false');
            });
            const esPuntos = tab.dataset.rutaTab === 'puntos';
            if (panelPuntos) panelPuntos.hidden = !esPuntos;
            if (panelLugares) panelLugares.hidden = esPuntos;
        });
    });

    root.querySelectorAll('[data-ruta-modo]').forEach((btn) => {
        btn.addEventListener('click', () => {
            root.querySelectorAll('[data-ruta-modo]').forEach((b) => b.classList.toggle('activo', b === btn));
        });
    });

    root.querySelector('[data-ruta-plegar]')?.addEventListener('click', () => {
        panel?.classList.toggle('plegado');
        setTimeout(() => mapa.invalidateSize(), 120);
    });

    const abrirActividad = (abierto) => {
        if (!actividadMenu || !actividadBtn) return;
        actividadMenu.hidden = !abierto;
        actividadBtn.setAttribute('aria-expanded', abierto ? 'true' : 'false');
    };

    actividadBtn?.addEventListener('click', (event) => {
        event.stopPropagation();
        abrirActividad(actividadMenu?.hidden !== false);
    });

    root.querySelectorAll('[data-ruta-actividad-opcion]').forEach((opcion) => {
        opcion.addEventListener('click', () => {
            root.querySelectorAll('[data-ruta-actividad-opcion]').forEach((o) => o.classList.toggle('activo', o === opcion));
            if (actividad) actividad.value = opcion.dataset.valor || 'Senderismo';
            if (actividadLabel) actividadLabel.textContent = opcion.dataset.valor || 'Senderismo';
            const iconoFuente = opcion.querySelector('.ruta-actividad-ico');
            if (actividadIco && iconoFuente) actividadIco.innerHTML = iconoFuente.innerHTML;
            abrirActividad(false);
            actualizarStats();
        });
    });

    document.addEventListener('click', (event) => {
        if (!event.target.closest('[data-ruta-actividad-wrap]')) abrirActividad(false);
    });

    root.querySelector('[data-ruta-nombre]')?.addEventListener('input', syncForm);

    const inicio = root.querySelector('[data-ruta-inicio]');
    const fin = root.querySelector('[data-ruta-fin]');
    const fechas = () => {
        if (!inicio || !fin) return;
        fin.min = inicio.value || fin.getAttribute('min') || '';
        fin.required = !!inicio.value;
        inicio.required = !!fin.value;
    };
    inicio?.addEventListener('change', fechas);
    fin?.addEventListener('change', fechas);
    fechas();

    actualizarStats();
    setTimeout(() => mapa.invalidateSize(), 100);
})();
