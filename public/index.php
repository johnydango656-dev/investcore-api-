function launchApp(method) {
    var isAndroid = /Android/i.test(navigator.userAgent);
    var isIOS = /iPhone|iPad|iPod/i.test(navigator.userAgent);

    if (method === 'GCash') {
        if (isAndroid) {
            // Android Intent para diretsong magbukas ang GCash app nang hindi nag-i-store sa Play Store
            window.location.href = "intent://#Intent;scheme=gcash;package=com.globe.gcash.android;end;";
        } else if (isIOS) {
            window.location.href = "gcash://app";
        } else {
            window.location.href = "gcash://app";
        }
    } else {
        // Maya Intent
        if (isAndroid) {
            window.location.href = "intent://#Intent;scheme=paymaya;package=com.paymaya;end;";
        } else {
            window.location.href = "paymaya://";
        }
    }
}
