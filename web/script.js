(function (w, d) {
    let elBody = d.body,
        elViewer = d.getElementById('viewer'),
        elTime = d.getElementById('time'),
        imgCount = w.images.length,
        baseUri = 'http://localhost/dotburo/bxl-window/',
        shown = [];

    toggleLoading();
    changeImage();

    function toggleLoading() {
        elBody.classList.toggle('loading')
    }

    function changeImage() {
        let img = chooseImage();
        elViewer.style.backgroundImage = `url(${img.url})`;
        elTime.textContent = img.time
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
            url: `${baseUri}/output/${w.images[i].name}`,
            time: w.images[i].time
        };
    }

    d.addEventListener('keydown', function(e) {
        if (e.key === ' ') {
            changeImage();
        }
    }, false);

    function getRandomIntInclusive(min, max) {
        min = Math.ceil(min);
        max = Math.floor(max);
        return Math.floor(Math.random() * (max - min + 1)) + min;
    }

})(window, document);