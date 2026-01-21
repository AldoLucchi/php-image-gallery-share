<?php
require_once __DIR__ . '/config/env.php';
session_start();

$imageDir = 'images/';
$cacheFile = 'image-cache.json';
$cacheTime = 60;

function getImagesWithCache($dir, $cacheFile, $cacheTime) {
    if (file_exists($cacheFile) && 
        (time() - filemtime($cacheFile) < $cacheTime)) {
        $cached = json_decode(file_get_contents($cacheFile), true);
        if ($cached !== null) return $cached;
    }
    
    $images = [];
    if (!is_dir($dir)) return $images;
    
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    $files = array_diff(scandir($dir), ['.', '..']);
    
    foreach ($files as $file) {
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (in_array($ext, $allowedExtensions)) {
            $path = $dir . $file;
            $info = @getimagesize($path);
            $images[] = [
                'src' => $path,
                'name' => $file,
                'width' => $info[0] ?? 800,
                'height' => $info[1] ?? 600,
                'mtime' => filemtime($path)
            ];
        }
    }
    
    usort($images, function($a, $b) {
        return $b['mtime'] - $a['mtime'];
    });
    
    file_put_contents($cacheFile, json_encode($images));
    return $images;
}

$images = getImagesWithCache($imageDir, $cacheFile, $cacheTime);

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

$isAuthenticated = isset($_SESSION['gallery_authenticated']) && $_SESSION['gallery_authenticated'] === true;
$sessionLifetime = 24 * 60 * 60;

if ($isAuthenticated && isset($_SESSION['login_time'])) {
    if (time() - $_SESSION['login_time'] > $sessionLifetime) {
        session_destroy();
        $isAuthenticated = false;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>❤️</title>
    <meta name="description" content="❤️">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.css">
    <script src="https://www.google.com/recaptcha/api.js?render=explicit&onload=onRecaptchaLoad" async defer></script>
    <style>
        body {
            margin: 0;
            font-family: system-ui, -apple-system, sans-serif;
            background-color: black;
        }

        #auth-container {
            display: <?php echo $isAuthenticated ? 'none' : 'flex'; ?>;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: #f5f5f5;
        }

        #login-form {
            padding: 2rem;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 400px;
            width: 90%;
        }

        #login-form p {
            color: black;
            font-weight: bold;
            margin-bottom: 1rem;
        }

        #password-input {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            box-sizing: border-box;
        }
        
        .g-recaptcha {
            margin: 20px 0;
        }
    
        #recaptcha-error {
            color: #ff4444;
            font-size: 14px;
            margin: 10px 0;
            min-height: 20px;
            display: none;
        }
    
        #login-btn:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }

        #error-message {
            color: red;
            min-height: 20px;
            margin: 10px 0;
        }

        #login-form button {
            width: 100%;
            padding: 12px;
            background-color: #0070f3;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            font-weight: bold;
        }

        #upload-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 12px 24px;
            background: linear-gradient(135deg, #4CAF50, #2E7D32);
            color: white;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            display: <?php echo $isAuthenticated ? 'block' : 'none'; ?>;
            z-index: 1000;
            font-weight: bold;
            font-size: 16px;
        }

        #file-input {
            display: none;
        }

        .justified-gallery {
            --padding: max(2.5vw, 12px);
            --space: max(2.5vw, 12px);
            --min-height: clamp(200px, 20vw, 400px);
            padding: var(--padding);
            display: flex;
            flex-wrap: wrap;
            gap: var(--space);
            display: <?php echo $isAuthenticated ? 'flex' : 'none'; ?>;
        }

        .justified-gallery a {
            flex-grow: calc(var(--width) * (100000 / var(--height)));
            flex-basis: calc(var(--min-height) * (var(--width) / var(--height)));
            aspect-ratio: var(--width) / var(--height);
            overflow: hidden;
            opacity: 1;
            transition: all 0.05s ease-in-out;
        }

        .justified-gallery a img {
            display: block;
            object-fit: cover;
            height: 100%;
            width: 100%;
        }

        .justified-gallery a:focus-visible {
            outline: 3px solid var(--outline);
            outline-offset: 2px;
            transform: scale(1.05);
            z-index: 1;
            border-radius: 2px;
            box-shadow: 0 2px 4px 2px rgba(0, 0, 0, 0.1);
        }

        .justified-gallery::after {
            content: " ";
            flex-grow: 1000000000;
        }

        #logout-button {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 10px 20px;
            color: white;
            border: none;
            border-radius: 9999px;
            cursor: pointer;
            display: <?php echo $isAuthenticated ? 'block' : 'none'; ?>;
            z-index: 1000;
            border-style: none;
            background-image: linear-gradient(83.21deg, #3245ff, #b845ed);
            --tw-text-opacity: 1;
            color: rgb(255 255 255 / var(--tw-text-opacity, 1));
            text-decoration: none;
            font-size: 16px;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div id="auth-container">
        <form id="login-form">
            <p><b>Insert important date</b></p>
            <input type="text" id="password-input" placeholder="dd.mm.yyyy" required>
            
            <div id="recaptcha-widget"></div>
            <div id="recaptcha-error">
                Please complete the CAPTCHA
            </div>
            
            <p id="error-message"></p>
            <button type="submit" id="login-btn" disabled>Open</button>
        </form>
    </div>

    <button id="upload-btn">📤</button>
    
    <input type="file" id="file-input" multiple accept="image/*">
    
    <section id="gallery-container" class="justified-gallery">
        <?php if ($isAuthenticated): ?>
        <?php foreach ($images as $index => $image): ?>
        <a href="<?php echo htmlspecialchars($image['src']); ?>" 
           data-fancybox="gallery" 
           style="--width: <?php echo $image['width']; ?>; --height: <?php echo $image['height']; ?>;">
            <img src="<?php echo htmlspecialchars($image['src']); ?>" 
                 alt="" 
                 loading="<?php echo $index < 20 ? 'eager' : 'lazy'; ?>">
        </a>
        <?php endforeach; ?>
        <?php endif; ?>
    </section>
    
    <a href="?logout=1" id="logout-button">Close</a>

    <script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.umd.js"></script>
    <script>
        let recaptchaWidgetId = null;
        let isCaptchaVerified = false;

        window.onRecaptchaLoad = function() {
            if (typeof grecaptcha !== 'undefined') {
                recaptchaWidgetId = grecaptcha.render('recaptcha-widget', {
                    'sitekey': '<?php echo $_ENV['RECAPTCHA_SITE_KEY'] ?? ''; ?>',
                    'callback': onRecaptchaSuccess,
                    'expired-callback': onRecaptchaExpired,
                    'error-callback': onRecaptchaError
                });
                
                setTimeout(function() {
                    if (!isCaptchaVerified) {
                        document.getElementById('login-btn').disabled = false;
                        document.getElementById('recaptcha-error').style.display = 'none';
                    }
                }, 3000);
            }
        };

        function onRecaptchaSuccess(token) {
            isCaptchaVerified = true;
            document.getElementById('recaptcha-error').style.display = 'none';
            document.getElementById('login-btn').disabled = false;
        }

        function onRecaptchaExpired() {
            isCaptchaVerified = false;
            document.getElementById('login-btn').disabled = true;
            document.getElementById('recaptcha-error').style.display = 'block';
            document.getElementById('recaptcha-error').textContent = 'CAPTCHA expired. Please verify again.';
        }

        function onRecaptchaError() {
            isCaptchaVerified = false;
            document.getElementById('login-btn').disabled = true;
            document.getElementById('recaptcha-error').style.display = 'block';
            document.getElementById('recaptcha-error').textContent = 'CAPTCHA error. Please refresh.';
        }

        document.addEventListener('DOMContentLoaded', function() {
            const authContainer = document.getElementById('auth-container');
            const galleryContainer = document.getElementById('gallery-container');
            const logoutButton = document.getElementById('logout-button');
            const loginForm = document.getElementById('login-form');
            const passwordInput = document.getElementById('password-input');
            const errorMessage = document.getElementById('error-message');
            const uploadBtn = document.getElementById('upload-btn');
            const fileInput = document.getElementById('file-input');
            const loginBtn = document.getElementById('login-btn');
            
            const isAuthenticated = <?php echo $isAuthenticated ? 'true' : 'false'; ?>;
            
            if (isAuthenticated) {
                showGallery();
            } else {
                showLogin();
            }
            
            loginForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const password = passwordInput.value.trim();
                const recaptchaToken = grecaptcha ? grecaptcha.getResponse() : null;
                
                const isLocal = window.location.hostname === 'localhost' || 
                               window.location.hostname === '127.0.0.1';
                
                if (!recaptchaToken && !isLocal) {
                    document.getElementById('recaptcha-error').style.display = 'block';
                    document.getElementById('recaptcha-error').textContent = 'Please complete the CAPTCHA';
                    return;
                }
                
                const originalText = loginBtn.innerHTML;
                loginBtn.innerHTML = '⏳ Verifying...';
                loginBtn.disabled = true;
                
                try {
                    const formData = new FormData();
                    formData.append('password', password);
                    
                    if (recaptchaToken) {
                        formData.append('recaptcha_token', recaptchaToken);
                    }
                    
                    const response = await fetch('verify-login.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        window.location.reload();
                    } else {
                        errorMessage.textContent = result.error || 'Login failed';
                        errorMessage.style.color = 'red';
                        
                        if (grecaptcha && grecaptcha.reset) {
                            grecaptcha.reset();
                        }
                        loginBtn.disabled = true;
                    }
                } catch (error) {
                    errorMessage.textContent = 'Network error, please try again';
                    errorMessage.style.color = 'red';
                    
                    if (grecaptcha && grecaptcha.reset) {
                        grecaptcha.reset();
                    }
                    loginBtn.disabled = true;
                } finally {
                    loginBtn.innerHTML = originalText;
                }
            });
            
            logoutButton.addEventListener('click', function(e) {
                e.preventDefault();
                window.location.href = '?logout=1';
            });
            
            uploadBtn.addEventListener('click', function() {
                fileInput.click();
            });
            
            fileInput.addEventListener('change', async function(e) {
                const target = e.target;
                if (!target || !target.files || target.files.length === 0) return;
                
                const files = Array.from(target.files);
                const formData = new FormData();
                files.forEach(function(file) {
                    formData.append('photos[]', file);
                });
                
                const originalText = uploadBtn.innerHTML;
                uploadBtn.innerHTML = '⏳ Uploading...';
                uploadBtn.disabled = true;
                
                try {
                    const response = await fetch('upload.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const result = await response.json();
                    
                    if (result.success && result.files) {
                        result.files.forEach(function(fileData) {
                            addImageToGallery(fileData.url, fileData.original || fileData.name);
                        });
                        
                        alert('✅ ' + (result.count || result.files.length) + ' photo(s) uploaded successfully!');
                        target.value = '';
                    } else {
                        alert('❌ Error: ' + (result.error || 'Upload failed'));
                    }
                } catch (error) {
                    alert('⚠️ Network error');
                } finally {
                    uploadBtn.innerHTML = originalText;
                    uploadBtn.disabled = false;
                }
            });
            
            function addImageToGallery(imageUrl, altText) {
                const gallery = document.getElementById('gallery-container');
                if (!gallery) return;
                
                if (!imageUrl) return;
                
                if (imageUrl && !imageUrl.startsWith('http') && !imageUrl.startsWith('/')) {
                    imageUrl = '/' + imageUrl;
                }
                
                const link = document.createElement('a');
                link.href = imageUrl;
                link.dataset.fancybox = 'gallery';
                
                const width = 800;
                const height = 600;
                
                link.style.cssText = `
                    --width: ${width}; 
                    --height: ${height}; 
                    flex-grow: calc(var(--width) * (100000 / var(--height))); 
                    flex-basis: calc(var(--min-height) * (var(--width) / var(--height))); 
                    aspect-ratio: var(--width) / var(--height); 
                    overflow: hidden;
                `;
                
                const img = document.createElement('img');
                img.src = imageUrl;
                img.alt = altText || 'Uploaded image';
                img.loading = 'lazy';
                img.style.cssText = `
                    display: block;
                    object-fit: cover;
                    height: 100%;
                    width: 100%;
                `;
                
                link.appendChild(img);
                gallery.prepend(link);
                
                Fancybox.bind(link, {
                    theme: "auto",
                    mainStyle: {
                        "--f-button-width": "44px",
                        "--f-button-height": "44px",
                    },
                    Carousel: {
                        Arrows: false,
                        Toolbar: {
                            display: {
                                left: [],
                                middle: [],
                                right: ["close"],
                            },
                        },
                    },
                });
            }
            
            function showGallery() {
                if (authContainer) authContainer.style.display = 'none';
                if (galleryContainer) galleryContainer.style.display = 'flex';
                if (logoutButton) logoutButton.style.display = 'block';
                if (uploadBtn) uploadBtn.style.display = 'block';
                
                Fancybox.bind("[data-fancybox]", {
                    theme: "auto",
                    mainStyle: {
                        "--f-button-width": "44px",
                        "--f-button-height": "44px",
                        "--f-button-border-radius": "50%",
                        "--f-toolbar-padding": "16px",
                    },
                    Carousel: {
                        Arrows: false,
                        Toolbar: {
                            display: {
                                left: [],
                                middle: [],
                                right: ["close"],
                            },
                        },
                        transition: "slide",
                    },
                });
            }
            
            function showLogin() {
                if (authContainer) authContainer.style.display = 'flex';
                if (galleryContainer) galleryContainer.style.display = 'none';
                if (logoutButton) logoutButton.style.display = 'none';
                if (uploadBtn) uploadBtn.style.display = 'none';
                
                if (passwordInput) {
                    passwordInput.focus();
                    passwordInput.value = '';
                }
                if (errorMessage) errorMessage.textContent = '';
            }
        });
    </script>
</body>
</html>
