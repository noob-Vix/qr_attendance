<!DOCTYPE html>
<html>
<head>
    <title>QR Scanner</title>
    <script src="https://rawgit.com/schmich/instascan-builds/master/instascan.min.js"></script>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 20px; 
            max-width: 800px; 
            margin: 0 auto; 
            text-align: center;
        }
        #preview { 
            width: 100%; 
            max-width: 500px; 
            margin: 0 auto; 
        }
        #result {
            margin: 20px;
            padding: 10px;
            background-color: #f0f0f0;
        }
    </style>
</head>
<body>
    <h2>Attendance Scanner</h2>
    <video id="preview"></video>
    <div id="result"></div>

    <script>
        let scanner = new Instascan.Scanner({ video: document.getElementById('preview') });
        
        scanner.addListener('scan', function (content) {
            document.getElementById('result').innerHTML = 'Scanning...';
            
            // Send to process_attendance.php
            fetch('process_attendance.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'qr_data=' + encodeURIComponent(content)
            })
            .then(response => response.text())
            .then(data => {
                document.getElementById('result').innerHTML = data;
            })
            .catch(error => {
                document.getElementById('result').innerHTML = 'Error: ' + error;
            });
        });

        // Start camera
        Instascan.Camera.getCameras().then(function (cameras) {
            if (cameras.length > 0) {
                // Try to use the back camera
                if (cameras[1]) {
                    scanner.start(cameras[1]); // Back camera
                } else {
                    scanner.start(cameras[0]); // Front camera if no back camera
                }
            } else {
                console.error('No cameras found.');
                alert('No cameras found.');
            }
        }).catch(function (e) {
            console.error(e);
            alert('Error accessing camera.');
        });
    </script>
    
</body>
</html>