console.log('SmartHR frontend loaded');

document.addEventListener('DOMContentLoaded', () => {
    const flash = document.querySelector('.alert');

    if (flash) {
        setTimeout(() => {
            flash.classList.add('fade-out');
            setTimeout(() => flash.remove(), 300);
        }, 5000);
    }
});
