(() => {
    const root = document.querySelector('[data-viaje]');
    if (!root || !window.L || !window.THAKI_VIAJE) return;

    const lugares = window.THAKI_VIAJE.lugares || [];
    const destinoInput = root.querySelector('[data-viaje-destino]');
    const crearBtn = root.querySelector('[data-viaje-crear]');
    const ficha = root.querySelector('[data-viaje-ficha]');
    const foto = root.querySelector('[data-viaje-foto]');
    const region = root.querySelector('[data-viaje-region]');
    const nombre = root.querySelector('[data-viaje-nombre]');
    const texto = root.querySelector('[data-viaje-texto]');
    const enlace = root.querySelector('[data-viaje-enlace]');
    const elegir = root.querySelector('[data-viaje-elegir]');
    const mapaNodo = document.getElementById('viaje-mapa');
    const marcadores = {};
    let seleccionado = window.THAKI_VIAJE.activo || null;

    const syncCrear = () => {
        if (crearBtn) crearBtn.disabled = !(destinoInput?.value || '').trim();
    };

    const slugDesdeDestino = () => {
        const option = destinoInput?.selectedOptions?.[0];
        return option?.dataset?.slug || null;
    };

    const mapa = L.map(mapaNodo, { zoomControl: false, scrollWheelZoom: true }).setView([-16.5, -65.5], 5.4);
    L.control.zoom({ position: 'topright' }).addTo(mapa);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 18,
        attribution: '&copy; OpenStreetMap'
    }).addTo(mapa);

    const icono = (lugar, activo) => L.divIcon({
        className: '',
        html: `<div class="viaje-marcador${activo ? ' activo' : ''}"><img src="${lugar.imagen}" alt="${lugar.nombre}"></div>`,
        iconSize: [54, 54],
        iconAnchor: [27, 27],
        popupAnchor: [0, -28]
    });

    const popupHtml = (lugar) => `
        <div class="viaje-popup">
            <img src="${lugar.imagen}" alt="${lugar.nombre}">
            <div>
                <span>${lugar.region}</span>
                <strong>${lugar.nombre}</strong>
                <p>${(lugar.descripcion || '').slice(0, 110)}${(lugar.descripcion || '').length > 110 ? '…' : ''}</p>
                <a href="${lugar.url}">Ver detalle ↗</a>
            </div>
        </div>`;

    const mostrarFicha = (lugar) => {
        if (!ficha) return;
        ficha.hidden = false;
        foto.src = lugar.imagen;
        foto.alt = lugar.nombre;
        region.textContent = lugar.region;
        nombre.textContent = lugar.nombre;
        texto.textContent = lugar.descripcion || '';
        enlace.href = lugar.url;
        elegir.dataset.nombre = lugar.nombre;
        elegir.dataset.slug = lugar.slug;
    };

    const activar = (slug, centrar = true) => {
        seleccionado = slug;
        Object.entries(marcadores).forEach(([clave, marker]) => {
            const lugar = lugares.find((item) => item.slug === clave);
            if (!lugar) return;
            marker.setIcon(icono(lugar, clave === slug));
        });
        const lugar = lugares.find((item) => item.slug === slug);
        if (!lugar) return;
        mostrarFicha(lugar);
        if (centrar) mapa.flyTo([lugar.lat, lugar.lng], Math.max(mapa.getZoom(), 7), { duration: 0.7 });
        if (destinoInput && destinoInput.value !== lugar.nombre) {
            destinoInput.value = lugar.nombre;
        }
        syncCrear();
    };

    const grupo = [];
    lugares.forEach((lugar) => {
        const marker = L.marker([lugar.lat, lugar.lng], { icon: icono(lugar, lugar.slug === seleccionado) })
            .addTo(mapa)
            .bindPopup(popupHtml(lugar), { maxWidth: 240 });
        marker.on('click', () => activar(lugar.slug, false));
        marcadores[lugar.slug] = marker;
        grupo.push([lugar.lat, lugar.lng]);
    });

    if (grupo.length) {
        mapa.fitBounds(grupo, { padding: [48, 48], maxZoom: 7 });
    }
    if (seleccionado) activar(seleccionado, true);

    destinoInput?.addEventListener('change', () => {
        const slug = slugDesdeDestino();
        if (slug) activar(slug, true);
        else syncCrear();
        marcadores[slug]?.openPopup();
    });

    elegir?.addEventListener('click', () => {
        if (elegir.dataset.slug) activar(elegir.dataset.slug, true);
        else if (elegir.dataset.nombre && destinoInput) {
            destinoInput.value = elegir.dataset.nombre;
            syncCrear();
        }
    });

    const inicio = document.getElementById('viaje-inicio');
    const fin = document.getElementById('viaje-fin');
    const fechas = () => {
        if (!inicio || !fin) return;
        fin.min = inicio.value || fin.getAttribute('min') || '';
        fin.required = !!inicio.value;
        inicio.required = !!fin.value;
    };
    inicio?.addEventListener('change', fechas);
    fin?.addEventListener('change', fechas);
    fechas();
    syncCrear();
    setTimeout(() => mapa.invalidateSize(), 80);
})();
