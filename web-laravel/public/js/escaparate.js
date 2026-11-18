document.querySelectorAll('[data-planificador]').forEach(form => {
    const inicio = form.querySelector('[name="fechaInicio"]');
    const fin = form.querySelector('[name="fechaFin"]');
    const minimo = fin.min;
    const fechas = () => {
        fin.min = inicio.value || minimo;
        fin.required = !!inicio.value;
        inicio.required = !!fin.value;
    };
    inicio.addEventListener('change', fechas);
    fin.addEventListener('change', fechas);
    fechas();
});
const armarCarrusel = (carrusel, controles, tarjetaSelector, gap = 22) => {
    if (!controles) return;
    const anterior = controles.querySelector('[data-destinos-anterior], [data-sugerencias-anterior]');
    const siguiente = controles.querySelector('[data-destinos-siguiente], [data-sugerencias-siguiente]');
    const actualizar = () => {
        const limite = carrusel.scrollWidth - carrusel.clientWidth;
        controles.hidden = limite < 2;
        anterior.disabled = carrusel.scrollLeft < 2;
        siguiente.disabled = carrusel.scrollLeft >= limite - 2;
    };
    const desplazar = direccion => {
        const tarjeta = carrusel.querySelector(tarjetaSelector);
        const salto = (tarjeta?.getBoundingClientRect().width || carrusel.clientWidth) + gap;
        carrusel.scrollBy({left: direccion * salto, behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'instant' : 'smooth'});
    };
    anterior.addEventListener('click', () => desplazar(-1));
    siguiente.addEventListener('click', () => desplazar(1));
    carrusel.addEventListener('scroll', actualizar, {passive: true});
    window.addEventListener('resize', actualizar);
    actualizar();
};
document.querySelectorAll('[data-destinos-carrusel]').forEach(carrusel => {
    armarCarrusel(carrusel, carrusel.parentElement.querySelector('[data-destinos-controles]'), '.tarjeta-destino', 22);
});
document.querySelectorAll('[data-sugerencias-carrusel]').forEach(carrusel => {
    armarCarrusel(carrusel, carrusel.parentElement.querySelector('[data-sugerencias-controles]'), '.sugerencia-tarjeta', 14);
});
