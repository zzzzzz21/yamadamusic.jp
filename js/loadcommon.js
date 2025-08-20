function loadFooter() {
    let xhttp = new XMLHttpRequest();
    xhttp.onload = function () {
        document.getElementById("footer").innerHTML =
                this.responseText;
    }
    xhttp.open("GET", "view/footer.html");
    xhttp.send();
}
function loadMenu() {
    let xhttp = new XMLHttpRequest();
    xhttp.onload = function () {
        document.getElementById("menubar").innerHTML =
                this.responseText;
    }
    xhttp.open("GET", "view/menubar.html");
    xhttp.send();
}

function loadMenuSp() {
    let xhttp = new XMLHttpRequest();
    xhttp.onload = function () {
        document.getElementById("menubar-s").innerHTML =
                this.responseText;
    }
    xhttp.open("GET", "view/menubar-s.html");
    xhttp.send();
}

jQuery(document).ready(function () {
    loadFooter();
    loadMenu();
    loadMenuSp();
    jQuery('footer').on('click', '#menubar_hdr', function () {
        if (OCwindowWidth() <= 800) {
            jQuery('#menubar_hdr').toggleClass('open');
            jQuery('#menubar-s').slideToggle();
        }
    });

    jQuery('footer').on('click','.gotop',function(){
        jQuery("html, body").animate({scrollTop: 0}, 600);
    });
});