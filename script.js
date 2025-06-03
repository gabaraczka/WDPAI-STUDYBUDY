document.addEventListener('DOMContentLoaded', function() {
    const uiHandler = new UIHandler();
    uiHandler.init();
});

class UIHandler {
    constructor() {
        this.hamburger = document.querySelector('.hamburger');
        this.navMenu = document.querySelector('.nav-menu');
        this.generateBtn = document.querySelector('.generate-btn');
    }

    init() {
        this.setupHamburger();
        this.setupGenerateBtn();
    }

    setupHamburger() {
        if (this.hamburger && this.navMenu) {
            this.hamburger.addEventListener('click', () => {
                this.navMenu.classList.toggle('active');
            });
        }
    }

    setupGenerateBtn() {
        if (this.generateBtn) {
            this.generateBtn.addEventListener('click', (e) => {
                e.preventDefault();
                window.location.href = "generate.php";
            });
        }
    }
}
