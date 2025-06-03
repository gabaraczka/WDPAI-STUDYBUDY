document.addEventListener("DOMContentLoaded", function () {
    const app = new UploadApp();
    app.init();
});

class UploadApp {
    constructor() {
        this.uploadForm = document.getElementById("uploadForm");
        this.fileInput = document.getElementById("fileInput");
        this.loading = document.getElementById("loading");
        this.summaryResult = document.getElementById("summaryResult");
        this.summaryText = document.getElementById("summaryText");
        this.triggerFileInput = document.getElementById("triggerFileInput");
        this.sendNoteButton = document.getElementById("sendNote");
        this.notesInput = document.getElementById("notesInput");
        this.folderSelect = document.getElementById("folderSelect");
        this.folderCheckboxes = document.querySelectorAll(".folder-checkbox");
        this.selectedFolderID = document.getElementById("selectedFolderID");
        this.hamburger = document.getElementById("hamburger");
        this.navMenu = document.getElementById("nav-menu");
        this.generateBtn = document.querySelector(".generate-btn");
        this.addFolderBtn = document.querySelector(".add-folder-btn");
        this.generateButton = document.getElementById("generateResponse");
        this.materialCheckboxes = document.querySelectorAll(".material-checkbox");
        this.deleteMaterialID = document.getElementById("deleteMaterialID");
        this.removeMaterialForm = document.querySelector(".remove-material-form");
        this.deleteButton = document.querySelector(".remove-material-btn");
    }

    init() {
        this.setupHamburger();
        this.setupRedirectToGenerate();
        this.setupSendNote();
        this.setupFileTriggers();
        this.setupUpload();
        this.setupAddFolder();
        this.setupGenerateSummary();
        this.setupMaterialDelete();
    }

    setupHamburger() {
        if (this.hamburger) {
            this.hamburger.addEventListener("click", () => {
                this.navMenu.classList.toggle("active");
            });
        }
    }

    setupRedirectToGenerate() {
        if (this.generateBtn) {
            this.generateBtn.addEventListener("click", (e) => {
                e.preventDefault();
                window.location.href = "generate.php";
            });
        }
    }

    setupSendNote() {
        if (this.sendNoteButton) {
            this.sendNoteButton.addEventListener("click", () => {
                const noteText = this.notesInput.value.trim();
                const selectedFolder = Array.from(this.folderCheckboxes)
                    .filter(cb => cb.checked)
                    .map(cb => cb.value);

                if (!noteText) return alert("Notatka jest pusta!");
                if (selectedFolder.length !== 1) return alert("Zaznacz dokładnie jeden folder.");

                fetch("generate.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({
                        noteText,
                        selectedFolders: selectedFolder
                    })
                })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            alert("✅ Notatka zapisana!");
                            location.reload();
                        } else {
                            alert("❌ Błąd: " + (data.error || "nieznany problem."));
                        }
                    })
                    .catch(err => {
                        console.error("❌ Błąd:", err);
                        alert("❌ Nie udało się zapisać notatki.");
                    });
            });
        }
    }

    setupFileTriggers() {
        const triggerFileInput = document.getElementById("triggerFileInput");
        const fileInput = document.getElementById("fileInput");
        const uploadForm = document.getElementById("uploadForm");
        const selectedFolderID = document.getElementById("selectedFolderID");
        const folderCheckboxes = document.querySelectorAll(".folder-checkbox");

        if (triggerFileInput && fileInput && uploadForm) {
            triggerFileInput.addEventListener("click", () => {
                const selectedFolder = Array.from(folderCheckboxes).find(cb => cb.checked);
                if (!selectedFolder) {
                    alert("Please select a folder before adding materials.");
                    return;
                }
                selectedFolderID.value = selectedFolder.value;
                fileInput.click();
            });

            fileInput.addEventListener("change", () => {
                if (fileInput.files.length > 0) {
                    uploadForm.submit();
                }
            });
        }
    }

    setupUpload() {
        if (this.uploadForm) {
            this.uploadForm.addEventListener("submit", (e) => {
                e.preventDefault();
                if (!this.fileInput.files.length) {
                    alert("Please select a file before generating a summary.");
                    return;
                }

                this.loading.classList.remove("hidden");
                this.summaryResult.classList.add("hidden");

                const formData = new FormData();
                formData.append("file", this.fileInput.files[0]);

                fetch("upload.php", {
                    method: "POST",
                    body: formData,
                })
                    .then(response => response.text())
                    .then(text => {
                        try {
                            const data = JSON.parse(text);
                            this.loading.classList.add("hidden");

                            if (data.error) {
                                alert("Error: " + data.error);
                                return;
                            }

                            this.summaryText.textContent = data.summary;
                            this.summaryResult.classList.remove("hidden");
                        } catch (e) {
                            alert("An error occurred. Check console for details.");
                            console.error("JSON Parsing Error:", e, "Response text:", text);
                        }
                    })
                    .catch(error => {
                        this.loading.classList.add("hidden");
                        alert("An error occurred while generating the summary.");
                        console.error(error);
                    });
            });
        }
    }

    setupAddFolder() {
        if (this.addFolderBtn) {
            this.addFolderBtn.addEventListener("click", () => {
                const folderName = prompt("Podaj nazwę folderu:");
                if (!folderName) return;

                fetch("create_folder.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ folderName }),
                })
                    .then((res) => res.text())
                    .then((text) => {
                        const data = JSON.parse(text);
                        if (data.success) {
                            alert("📁 Folder dodany!");
                            location.reload();
                        } else {
                            alert("❌ " + data.error);
                        }
                    })
                    .catch((err) => {
                        console.error("❌ Błąd JSON lub fetch:", err);
                        alert("Wystąpił błąd (brak JSON lub odpowiedź HTML).");
                    });
            });
        }
    }

    setupGenerateSummary() {
        if (this.generateButton) {
            this.generateButton.addEventListener("click", () => {
                const selectedFiles = Array.from(this.materialCheckboxes)
                    .filter(cb => cb.checked)
                    .map(cb => cb.value);

                if (selectedFiles.length === 0) {
                    alert("Please select at least one file.");
                    return;
                }

                this.loading.classList.remove("hidden");

                fetch("generate.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                    },
                    body: JSON.stringify({ selectedFiles })
                })
                    .then(response => response.json())
                    .then(data => {
                        this.loading.classList.add("hidden");
                        console.log("ODPOWIEDŹ Z BACKENDU:", data);

                        if (data.success) {
                            this.summaryResult.classList.remove("hidden");
                            this.summaryText.innerHTML = data.data.map(file => `
                                <div class="summary-block">
                                    <h3>${file.material_name}</h3>
                                    <p>${file.summary}</p>
                                </div>
                            `).join("");
                        } else {
                            alert(data.error || "Wystąpił błąd.");
                        }
                    })
                    .catch(error => {
                        this.loading.classList.add("hidden");
                        console.error("Błąd:", error);
                        alert("Błąd podczas generowania streszczenia.");
                    });
            });
        }
    }

    setupMaterialDelete() {
        const deleteButton = document.querySelector(".remove-item-btn");
        const deleteIDInput = document.getElementById("deleteID");
        const deleteTypeInput = document.getElementById("deleteType");
        const materialCheckboxes = document.querySelectorAll(".material-checkbox");
        const folderCheckboxes = document.querySelectorAll(".folder-checkbox");

        if (deleteButton) {
            deleteButton.addEventListener("click", (e) => {
                e.preventDefault();

                // Check for selected materials first
                const selectedMaterial = Array.from(materialCheckboxes).find(cb => cb.checked);
                if (selectedMaterial) {
                    deleteIDInput.value = selectedMaterial.value;
                    deleteTypeInput.value = 'material';
                    if (confirm('Are you sure you want to delete this material?')) {
                        deleteButton.closest('form').submit();
                    }
                    return;
                }

                // Then check for selected folders
                const selectedFolder = Array.from(folderCheckboxes).find(cb => cb.checked);
                if (selectedFolder) {
                    deleteIDInput.value = selectedFolder.value;
                    deleteTypeInput.value = 'folder';
                    if (confirm('Are you sure you want to delete this folder and all its contents?')) {
                        deleteButton.closest('form').submit();
                    }
                    return;
                }

                alert('Please select a material or folder to delete.');
            });
        }
    }
}
