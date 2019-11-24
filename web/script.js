(function (w, d) {
    let elBody = d.body,
        elViewer = d.getElementById('viewer'),
        elTime = d.getElementById('time'),
        elUsage = d.getElementById('usage'),
        imgCount = w.images.length,
        baseUri = 'http://localhost/dotburo/bxl-window/',
        isTouch = 'ontouchstart' in w || (w.DocumentTouch && d instanceof DocumentTouch) || navigator.msMaxTouchPoints > 0,
        shown = [];

    elUsage.textContent = !isTouch ? '(use the space bar)' : '(touch to change)';

    changeImage();

    function changeImage() {
        toggleLoading(1);

        let img = chooseImage();

        loadImage(img.url)
            .then(() => {
                elViewer.style.backgroundImage = `url(${img.url})`;
                elTime.textContent = img.time;
                toggleLoading(0);
            })
            .catch(() => toggleLoading(0))
    }

    function chooseImage() {
        let i = getRandomIntInclusive(0, imgCount - 1);

        if (shown.indexOf(i) > -1) {
            if (shown.length === w.images.length) {
                shown = [];
            }
            return chooseImage();
        }

        shown.push(i);

        return {
            url: `${baseUri}/${w.images[i].name}`,
            time: w.images[i].time
        };
    }

    d.addEventListener('keydown', function (e) {
        if (e.key === ' ') {
            changeImage();
            hideUsage();
        }
    }, false);

    d.addEventListener('touchstart', function (e) {
        changeImage();
        hideUsage();
    }, false);

    function toggleLoading(state) {
        elBody.classList[state ? 'add' : 'remove']('loading')
    }

    function loadImage(src) {
        return new Promise((resolve, reject) => {
            let img = new Image();
            img.src = src;
            img.onload = () => resolve(img);
            img.onerror = e => reject(e);
        })
    }

    function getRandomIntInclusive(min, max) {
        min = Math.ceil(min);
        max = Math.floor(max);
        return Math.floor(Math.random() * (max - min + 1)) + min;
    }

    function hideUsage() {
        elUsage.style.display = 'none';
    }

})(window, document);
