   function selectPackage(amount, summaryText) {
            document.getElementById('selected-amount').value = amount;
            document.getElementById('summary-bold-text').innerHTML = summaryText;
            document.getElementById('payment-modal-overlay').style.display = 'flex';
            resetButtonState();
        }

        function closeModal() {
            document.getElementById('payment-modal-overlay').style.display = 'none';
        }

        function resetButtonState() {
            var payBtn = document.getElementById("submit-payment-btn");
            payBtn.style.backgroundColor = "#f15a24";
            payBtn.innerHTML = "PAY";
        }

        function detectMobileProvider() {
            var phoneInput = document.getElementById("phone-number").value.trim();
            var cleanDigits = phoneInput.replace(/[^0-9]/g, '');
            var payBtn = document.getElementById("submit-payment-btn");

            var standardizedDigits = cleanDigits;
            if (standardizedDigits.startsWith('0')) {
                standardizedDigits = '255' + standardizedDigits.substring(1);
            }
            
            var prefix = standardizedDigits.substring(3, 5);

            // Brand Hex Color Codes
            var mpesaColor    = "#E60000"; // Vodacom Red
            var tigoColor     = "#0033A0"; // Tigo Corporate Blue
            var airtelColor   = "#FF0000"; // Airtel Orange
            var haloloColor   = "#ffcc00"; // Halotel Yellow
            var unknownColor  = "#555555"; // Default

            if (['74', '75', '76', '14'].includes(prefix)) {
                payBtn.style.backgroundColor = mpesaColor;
                payBtn.innerHTML = "PAY (M-Pesa)";
            } else if (['71', '77', '65', '07', '67', '72', '70'].includes(prefix)) {
                payBtn.style.backgroundColor = tigoColor;
                payBtn.innerHTML = "PAY (TigoPesa)";
            } else if (['78', '79', '68', '69'].includes(prefix)) {
                payBtn.style.backgroundColor = airtelColor;
                payBtn.innerHTML = "PAY (Airtel Money)";
            } else if (['62', '61'].includes(prefix)) {
                payBtn.style.backgroundColor = haloloColor;
                payBtn.innerHTML = "PAY (Halopesa)";
            } else {
                payBtn.style.backgroundColor = unknownColor;
                payBtn.innerHTML = "PAY";
            }
        }
<script>

function dispatchToRailway(event) {
    // A. Reach back to grab the dynamic MAC text string from the router welcome page context
    var detectedMac = "0";
    try {
        var sourceDocument = window.opener ? window.opener.document : (window.parent ? window.parent.document : null);
        if (sourceDocument) {
            var cells = sourceDocument.getElementsByTagName('td');
            for (var i = 0; i < cells.length; i++) {
                if (cells[i].innerText.includes("MAC Address:")) {
                    if (cells[i+1]) {
                        // Strips out colons to cleanly format alphanumeric values for the NMS
                        detectedMac = cells[i+1].innerText.replace(/[^a-zA-Z0-9]/g, '').trim();
                        break;
                    }
                }
            }
        }
    } catch (e) {
        console.log("Cross-origin bridge notice: Router page scraping bypassed. Using default parameter.");
    }

    // B. Save the extracted MAC text value inside our hidden form field string
    document.getElementById('hidden_mac_field').value = detectedMac;

    // C. Perform your mobile operator routing/prefix checks
    var phone = document.getElementById("phone-number").value.trim();
    var carrierPrefix = phone.substring(1, 3);
    var allValidPrefixes = ['74', '75', '76', '71', '77', '65', '67', '68', '69', '62', '61'];

    if (allValidPrefixes.includes(carrierPrefix)) {
        document.getElementById("active-spinner-layer").style.setProperty("display", "flex", "important");
        return true; // Submits naturally carrying amount, customer_phone, AND mac_address!
    } else {
        event.preventDefault();
        alert("Mtandao hautambuliki! Tafadhali ingiza nambari sahihi.");
        return false;
    }
}
</script>


            // 🌫️ ACTIVATE THE 10-DOT GLASS LOADING OVERLAY
            document.getElementById("active-spinner-layer").style.setProperty("display", "flex", "important");
            
            // Forward form transaction straight to login processing engine
            document.getElementById("payment-form").submit();
        }
