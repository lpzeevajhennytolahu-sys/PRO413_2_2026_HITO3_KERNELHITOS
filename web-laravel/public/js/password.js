document.querySelectorAll('[data-password-toggle]').forEach(button => {
    const input = document.getElementById(button.getAttribute('aria-controls'));
    if (!input || input.type !== 'password') return;
    const description = (button.getAttribute('aria-label') || '').replace(/^Mostrar /, '');
    button.hidden = false;

    const pestanear = () => {
        button.classList.remove('pestanando');
        void button.offsetWidth;
        button.classList.add('pestanando');
    };

    button.addEventListener('click', () => {
        const visible = input.type === 'password';
        pestanear();
        input.type = visible ? 'text' : 'password';
        button.classList.toggle('ojo-cerrado', visible);
        button.setAttribute('aria-pressed', String(visible));
        button.setAttribute('aria-label', `${visible ? 'Ocultar' : 'Mostrar'} ${description}`);
    });
});
