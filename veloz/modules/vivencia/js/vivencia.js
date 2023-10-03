document.addEventListener('DOMContentLoaded', function() {
    console.log('Vivencia loaded');
    // Sends a request to /veloz/json/get-bind-data
    // to get the data to bind the vivencia
    // to the view
    
    // Request
    let request = new XMLHttpRequest();

    // Request open
    request.open('GET', '/veloz/json/get-bind-data', true);

    // Request onload
    request.onload = function() {
        if (request.status >= 200 && request.status < 400) {
            let response = JSON.parse(request.responseText) ?? {};
            let data = response;

            if (!response) {
                console.error('No data');
                return;
            }

            // Loop through all objects within the array, and use bind-to for the name and value for the value
            for (let i = 0; i < data.length; i++) {
                let obj = data[i];

                let name = obj.bind_to;
                let value = obj.value;

                // Get all elements with the name
                let elements = document.getElementsByName(name);

                // Loop through all elements
                for (let j = 0; j < elements.length; j++) {
                    let element = elements[j];

                    // Check if the element is an input
                    if (element.tagName.toLowerCase() === 'input') {
                        // Check if the input is a checkbox
                        if (element.type === 'checkbox') {
                            // Check if the value is 1
                            if (value === 1) {
                                element.checked = true;
                            } else {
                                element.checked = false;
                            }
                        } else {
                            // Set the value
                            element.value = value;
                        }
                    } else if (element.tagName.toLowerCase() === 'select') {
                        // Loop through all options
                        for (let k = 0; k < element.options.length; k++) {
                            let option = element.options[k];

                            // Check if the option value is the same as the value
                            if (option.value === value) {
                                // Set the selected option
                                option.selected = true;
                            }
                        }
                    } else if (element.tagName.toLowerCase() === 'textarea') {
                        // Set the value
                        element.value = value;
                    } else if (element.tagName.toLowerCase() === 'img') {
                        // Set the src
                        element.src = value;
                    } else if (element.tagName.toLowerCase() === 'a') {
                        // Set the href
                        element.href = value;
                    } else if (element.tagName.toLowerCase() === 'span') {
                        // Set the innerHTML
                        element.innerHTML = value;
                    }
                }
            }

        } else {
            // We reached our target server, but it returned an error
            console.error('Failed to load data');
        }
    };

    request.onerror = function() {
        // There was a connection error of some sort
        console.error('Failed to connect to server');
    };

    request.send();
});