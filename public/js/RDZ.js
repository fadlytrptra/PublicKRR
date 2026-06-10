const channel = new BroadcastChannel("session_channel");
const TAB_ID = Date.now() + "_" + Math.random();

const LEADER_KEY = "heartbeat_leader";
const LEADER_TS_KEY = "heartbeat_leader_ts";
const SESSION_EXPIRED_KEY = "session_expired";

const HEARTBEAT_INTERVAL = 1810000; // 30 minutes + 10 sec buffer
const LEADER_TIMEOUT = 1850000; // slightly > heartbeat

// Untuk testing, gunakan interval yang lebih pendek
// const HEARTBEAT_INTERVAL = 61000; // 1 min + 1 sec buffer
// const LEADER_TIMEOUT = 70000; // slightly > heartbeat

let isLeader = false;
let lastCheck = 0;
const MIN_INTERVAL = 60 * 1000; // 10 minute

// 🔁 Try to become leader
function tryBecomeLeader() {
    const now = Date.now();
    const leader = localStorage.getItem(LEADER_KEY);
    const leaderTs = parseInt(localStorage.getItem(LEADER_TS_KEY), 10);
    // No leader OR leader is dead
    if (!leader || !leaderTs || now - leaderTs > LEADER_TIMEOUT) {
        localStorage.setItem(LEADER_KEY, TAB_ID);
        localStorage.setItem(LEADER_TS_KEY, now);
        isLeader = true;
    } else if (leader === TAB_ID) {
        isLeader = true;
    } else {
        isLeader = false;
    }
}

channel.onmessage = (event) => {
    if (event.data === "session_expired") {
        window.location.href = "/sessionexpired";
    }
};

function triggerSessionExpired() {
    channel.postMessage("session_expired");
    window.location.href = "/sessionexpired";
}

$(document).ajaxError(function (event, xhr) {
    if (xhr.status === 419) {
        triggerSessionExpired();
    }
});

setInterval(() => {
    tryBecomeLeader();

    if (!isLeader) return;

    // Update leader timestamp (I'm alive)
    localStorage.setItem(LEADER_TS_KEY, Date.now());

    fetch("/heartbeat", {
        method: "GET",
        headers: {
            "X-Requested-With": "XMLHttpRequest",
        },
    })
        .then((res) => {
            console.log({
                status: res.status,
                redirected: res.redirected,
                url: res.url,
            });
            if (res.status === 401 || res.status === 419 || res.redirected) {
                triggerSessionExpired();
            }
        })
        .catch(() => {
            triggerSessionExpired();
        });
}, HEARTBEAT_INTERVAL);

window.addEventListener("beforeunload", () => {
    const leader = localStorage.getItem(LEADER_KEY);
    if (leader === TAB_ID) {
        localStorage.removeItem(LEADER_KEY);
        localStorage.removeItem(LEADER_TS_KEY);
    }
});

document.addEventListener("visibilitychange", () => {
    if (document.visibilityState === "visible") {
        const now = Date.now();

        if (now - lastCheck < MIN_INTERVAL) {
            return; // skip, too soon
        }

        lastCheck = now;
        if (!isLeader) {
            tryBecomeLeader();
        }

        if (isLeader) {
            fetch("/heartbeat", {
                method: "GET",
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                },
            })
                .then((res) => {
                    if (
                        res.status === 401 ||
                        res.status === 419 ||
                        res.redirected
                    ) {
                        triggerSessionExpired();
                    }
                })
                .catch(() => {
                    triggerSessionExpired();
                });
        }
    }
});

// Debugging: Tampilkan sisa waktu sebelum heartbeat berikutnya
// juga digunakan untuk mendeteksi throttle atau masalah lain yang menyebabkan heartbeat tidak berjalan tepat waktu
// let startTime = Date.now();
// setInterval(() => {
//     let remaining = HEARTBEAT_INTERVAL - (Date.now() - startTime);
//     console.log(
//         "Remaining:",
//         numeral(remaining / 1000).format("0,0"),
//         "seconds",
//     );
// }, 10000);

function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);

    if (parts.length === 2) {
        return parts.pop().split(";").shift();
    }

    return null;
}

function getCsrfToken() {
    const token = getCookie("XSRF-TOKEN");
    return token ? decodeURIComponent(token) : null;
}

function refreshCsrfToken() {
    const token = getCsrfToken();

    fetch("/refresh-csrf", {
        method: "POST",
        credentials: "same-origin",
        headers: {
            "X-XSRF-TOKEN": token,
            Accept: "application/json",
        },
    })
        .then((response) => response.json())
        .then(() => {
            console.log("CSRF token refreshed");
        })
        .catch((err) => {
            console.error("Failed to refresh CSRF token:", err);
        });
}

if (window.$) {
    $.ajaxSetup({
        beforeSend: function (xhr) {
            const token = getCsrfToken();

            if (token) {
                xhr.setRequestHeader("X-XSRF-TOKEN", token);
            }
        },
    });
}

setInterval(refreshCsrfToken, 3600000);
