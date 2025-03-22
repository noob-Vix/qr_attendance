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
            margin: 50px auto; 
        }
        #result {
            margin: 20px;
            padding: 10px;
            background-color: #f0f0f0;
        }
        .back-btn {
            background-color: #666;
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>Attendance Scanner</h2> <a href="teacher_dashboard.php" class="back-btn">Back to Dashboard</a>
        
    </div>
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
                // More reliable camera selection
                scanner.start(cameras[0]); // Use first available camera
            } else {
                console.error('No cameras found.');
                document.getElementById('result').innerHTML = 'Error: No cameras found.';
            }
        }).catch(function (e) {
            console.error(e);
            document.getElementById('result').innerHTML = 'Error accessing camera: ' + e;
        });
    </script>
    
</body>
</html>