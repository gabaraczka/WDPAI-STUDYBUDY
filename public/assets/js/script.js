document.addEventListener('DOMContentLoaded', function() {
    const uiHandler = new UIHandler();
    uiHandler.init();
});

class UIHandler {
    constructor() {
        this.generateBtn = document.querySelector('.generate-btn');
    }

    init() {
        this.setupGenerateBtn();
    }

    setupGenerateBtn() {
        if (this.generateBtn) {
            this.generateBtn.addEventListener('click', (e) => {
                e.preventDefault();
                window.location.href = "/generate";
            });
        }
    }
}
