const headerBottom = document.querySelector('#header-bottom');
const headerBottomY = headerBottom.getBoundingClientRect().top + window.scrollY;
const headerBottomYTemp = headerBottomY;
const body = document.querySelector('body');

function pinHeaderBottomOnTop() {
    const scrollY = window.scrollY;

    if (scrollY >= headerBottomYTemp) {
        headerBottom.style.position = "fixed";
        body.style.paddingTop = headerBottom.offsetHeight + 'px';
    } else {
        headerBottom.style.position = "";
        body.style.paddingTop = 0 + 'px';
    }
}

window.addEventListener('scroll', pinHeaderBottomOnTop);
