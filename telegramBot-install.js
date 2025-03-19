class InstallationForm {
    constructor() {
        this.initEventListeners();
    }

    initEventListeners() {
        document.getElementById("logofile").addEventListener("change", (event) => this.handleFileUpload(event));
        document.getElementById("addButton").addEventListener("click", () => this.cloneRequest());
        document.getElementById("installationForm").addEventListener("submit", (event) => this.handleSubmit(event));
        window.onload = () => this.restoreFromAppStorage();
    }

    handleFileUpload(event) {
        const file = event.target.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.readAsDataURL(file);

        reader.onload = () => document.getElementById("base64format").value = reader.result;

    }

    setFileToInput(base64, filename = "logo") {
        let arr = base64.split(',');
        if (arr[0].match(/:(.*?);/)) {
            let mime = arr[0].match(/:(.*?);/)[1], // Extract MIME type
                bstr = atob(arr[1]), // Decode Base64
                n = bstr.length,
                u8arr = new Uint8Array(n);
            let extension = mime.replace("image/", ".");
            filename = filename + extension;
            while (n--) {
                u8arr[n] = bstr.charCodeAt(n);
            }

            return new File([u8arr], filename, {type: mime});
        }

    }

    cloneRequest(requestNumber = null, value = "") {

        let container = document.createElement("div");
        container.classList.add("request-item");
        container.classList.add("mb-3");

        let inputField = document.createElement("textarea");
        inputField.classList.add("form-control");
        inputField.classList.add("request-textarea");

        if (inputField) {
            inputField.value = value;// Reset input to empty
        }

        let requestNum = document.createElement("span");
        requestNum.classList.add("requestnumber");

        let newRequestNumber = requestNumber ? requestNumber : document.querySelectorAll("#request-add-block .requestnumber").length + 1;
        if (requestNum) {
            requestNum.textContent = newRequestNumber;
        }

        let deleteBtn = document.createElement("button");
        deleteBtn.textContent = "Удалить";
        deleteBtn.classList = "btn btn-danger btn-sm";
        deleteBtn.onclick = () => {
            container.remove();
            this.updateRequestNumbers();
        };

        // Append elements
        container.appendChild(document.createTextNode("Запрос "));
        container.appendChild(requestNum);
        container.appendChild(inputField);
        container.appendChild(deleteBtn);
        document.getElementById("request-add-block").appendChild(container);
    }

    updateRequestNumbers() {
        document.querySelectorAll("#request-add-block .requestnumber").forEach((el, index) => {
            el.textContent = index + 1;
        });
    }

    telegramSetWebhook(token, url) {
        const requestOptions = {
            method: "GET",
            redirect: "follow"
        };
        if (token && url) {
            fetch("https://api.telegram.org/bot" + token + "/setWebhook?url=" + url, requestOptions)
                .then((response) => response.text())
                .then((result) => console.log(result))
                .catch((error) =>{console.error(error);document.getElementById("appResultMessageError").style.display="block"})
        }

    }

    handleSubmit(event) {
        event.preventDefault();
        const handleUrl = "https://cp.micros.uz/dev/sher/handler.php";
        const token = document.getElementById("tokentelegrambot").value;
        const connectorname = document.getElementById("connectorname").value;
        const logofile = document.getElementById("base64format").value;
        let requestTextareas = document.querySelectorAll("#request-add-block .request-textarea");
        let requestValues = [];
        let appOptions = {};

        requestTextareas.forEach(input => {
            requestValues.push(input.value);
        });

        appOptions.requestlist = Object.assign({}, requestValues);
        appOptions.token = token;
        appOptions.connectorname = connectorname;
        appOptions.logofile = logofile;

        this.telegramSetWebhook(token, handleUrl);

        BX24.callMethod(
            'app.option.set',
            {
                "options": appOptions
            },
            function (result) {
                if (result.error()) {
                    console.error(result.error());
                }
            }
        );

        BX24.callMethod(
            'entity.add',
            {
                'ENTITY': 'telegramusers',
                'NAME': 'Данные пользователей Телеграм бота',
            }
        );
        const dataImage = document.getElementById("base64format").value;
        const connector = document.getElementById("connectorname").value || "Открытие линия - Micros Telegram bot";

        const params = {
            ID: "micros_telegram_bot",
            NAME: connector,
            ICON: {
                DATA_IMAGE: dataImage,
                COLOR: "rgb(47 198 246)",
                SIZE: "90%",
                POSITION: "center"
            },
            ICON_DISABLED: {
                DATA_IMAGE: dataImage,
                COLOR: "rgb(47 198 246)",
                SIZE: "90%",
                POSITION: "center"
            },
            PLACEMENT_HANDLER: handleUrl,
            CHAT_GROUP: "N"
        };

        BX24.callMethod('imconnector.register', params, (result) => {
            if (result.error()) {
                alert("Error: " + result.error());
                return;
            }else{
                console.log(result.data());

            }
            document.getElementById("appResultMessageSuccess").style.display="block";
            setTimeout(function (){ document.getElementById("appResultMessageSuccess").style.display="none";},3000)
            this.bindEvents(handleUrl);

            BX24.installFinish();
        });
    }

    bindEvents(handleUrl) {
        const events = [
            'ONIMCONNECTORMESSAGEADD',
            'ONIMCONNECTORMESSAGEUPDATE',
            'ONIMCONNECTORMESSAGEDELETE'
        ];

        events.forEach(event => {
            BX24.callBind(event, handleUrl, (arResult) => {
                if (!arResult) {
                    console.error(`Ошибка привязки обработчика для ${event}:`, BX24.callMethod('events', {}, console.log));
                }
            });
        });
    }

    restoreFromAppStorage() {

        BX24.callMethod('app.option.get', {}, (result) => {
            if (result.error()) {
                console.error(result.error());
            } else {
                let appOptionData = result.data();
                document.getElementById("base64format").value = appOptionData.logofile;
                let file = this.setFileToInput(appOptionData.logofile);
                if(file){
                    let dataTransfer = new DataTransfer();
                    dataTransfer.items.add(file);

                    document.getElementById("logofile").files = dataTransfer.files;
                }

                let dataRequestList = appOptionData.requestlist;
                document.getElementById("connectorname").value = appOptionData.connectorname;

                document.getElementById("tokentelegrambot").value = appOptionData.token;

                for (let index = 0; index < dataRequestList.length; ++index) {
                    this.cloneRequest(index + 1, dataRequestList[index]); // Ensure `this` is bound correctly
                }
            }
        });
    }
}

// Initialize the form handler
document.addEventListener("DOMContentLoaded", () => new InstallationForm());

