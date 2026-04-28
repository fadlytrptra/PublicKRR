function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);

    if (parts.length === 2) {
        return parts.pop().split(';').shift();
    }

    return null;
}

function getCsrfToken() {
    const token = getCookie('XSRF-TOKEN');
    return token ? decodeURIComponent(token) : null;
}

function refreshCsrfToken() {
    const token = getCsrfToken();

    fetch('/refresh-csrf', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'X-XSRF-TOKEN': token,
            'Accept': 'application/json'
        }
    })
    .then((response) => response.json())
    .then(() => {
        console.log('CSRF token refreshed');
    })
    .catch((err) => {
        console.error('Failed to refresh CSRF token:', err);
    });
}

if (window.$) {
    $.ajaxSetup({
        beforeSend: function(xhr) {
            const token = getCsrfToken();

            if (token) {
                xhr.setRequestHeader('X-XSRF-TOKEN', token);
            }
        }
    });
}

setInterval(refreshCsrfToken, 3600000);
