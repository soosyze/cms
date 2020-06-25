$().ready(function () {
    var nestedSortables = [].slice.call($('.nested-sortable-role'));

    for (var i = 0; i < nestedSortables.length; i++) {
        new Sortable(nestedSortables[i], {
            animation: 150,
            handle: '.draggable',
            onEnd: function (evt) {
                sortRole("#main_sortable");
            }
        });
    }

    function sortRole(idMenu) {
        var weight = 1;

        $(idMenu).find('input[name^="role_weight"]').each(function () {
            $(this).val(weight);
            weight++;
        });
    }
});

function togglePassword(button, idPasswordInput) {
    let passwordInput = document.getElementById(idPasswordInput);

    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        button.firstChild.classList.add("fa-eye-slash");
    } else {
        passwordInput.type = 'password';
        button.firstChild.classList.remove("fa-eye-slash");
    }
}

function getRandomColor() {
    const letters = "0123456789abcdef";
    let color = "#";
    for (var i = 0; i < 6; i++) {
        color += letters[Math.floor(Math.random() * 16)];
    }

    return color;
}

function passwordPolicy(idPasswordInput)
{
    const value    = idPasswordInput.value;
    const elements = document.querySelectorAll('#password_policy li');

    elements.forEach(function (el, i) {
        const reg = new RegExp(el.dataset.pattern);
        if (reg.test(value)) {
            el.style.color = 'green';
        } else {
            el.style.color = 'inherit';
        }
    });
}