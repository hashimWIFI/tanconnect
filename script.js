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
                payBtn.innerHTML = "PAY (Airt Money)";
            } else if (['62', '61'].includes(prefix)) {
                payBtn.style.backgroundColor = haloloColor;
                payBtn.innerHTML = "PAY (Halopesa)";
            } else {
                payBtn.style.backgroundColor = unknownColor;
                payBtn.innerHTML = "PAY";
            }
        }

        function dispatchToRailway(event) {
            event.preventDefault();
            
            var phoneInput = document.getElementById("phone-number").value.trim();
            var cleanDigitsOnly = phoneInput.replace(/[^0-9]/g, '');

            if (phoneInput === "") {
                alert("Tafadhali ingiza namba ya simu kwanza.");
                return;
            }
            if (cleanDigitsOnly.length < 10) {
                alert("Namba uliyoingiza imepungua! Tafadhali ingiza namba kamili yenye tarakimu 10.");
                return;
            }
            if (cleanDigitsOnly.length > 10) {
                alert("Namba uliyoingiza imezidi! Tafadhali hakikisha namba yako ina tarakimu 10 pekee.");
                return;
            }

            var standardizedDigits = cleanDigitsOnly;
            if (standardizedDigits.startsWith('0')) {
                standardizedDigits = '255' + standardizedDigits.substring(1);
            }
            var carrierPrefix = standardizedDigits.substring(3, 5);

            var validVodacom = ['74', '75', '76', '14'];
            var validTigo    = ['71', '77', '65', '07', '67', '72', '70'];
            var validAirtel  = ['78', '79', '68', '69'];
            var validHalotel = ['62', '61'];
            var allValidPrefixes = validVodacom.concat(validTigo, validAirtel, validHalotel);

            if (!allValidPrefixes.includes(carrierPrefix)) {
                alert("Mtandao hautambuliki! Tafadhali ingiza nambari ya Vodacom, Tigo, Airtel, au Halotel.");
                return;
            }

            // 🌫️ ACTIVATE THE 10-DOT GLASS LOADING OVERLAY
            document.getElementById("active-spinner-layer").style.setProperty("display", "flex", "important");
            
            // Forward form transaction straight to login processing engine
            document.getElementById("payment-form").submit();
        }
