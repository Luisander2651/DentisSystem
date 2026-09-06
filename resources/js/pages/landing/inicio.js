function initFaqAccordion() {
    var items = document.querySelectorAll('.faq-item');
    items.forEach(function (item) {
        var trigger = item.querySelector('.faq-trigger');
        var content = item.querySelector('.faq-content');
        var icon = item.querySelector('.faq-icon');

        if (trigger && content) {
            trigger.addEventListener('click', function () {
                var isOpen = content.style.maxHeight && content.style.maxHeight !== '0px';

                // Close all other accordions for premium feel
                items.forEach(function (otherItem) {
                    var otherContent = otherItem.querySelector('.faq-content');
                    var otherIcon = otherItem.querySelector('.faq-icon');
                    if (otherContent && otherContent !== content) {
                        otherContent.style.maxHeight = '0px';
                    }
                    if (otherIcon && otherIcon !== icon) {
                        otherIcon.textContent = '+';
                        otherIcon.style.transform = 'rotate(0deg)';
                    }
                });

                if (isOpen) {
                    content.style.maxHeight = '0px';
                    icon.textContent = '+';
                    icon.style.transform = 'rotate(0deg)';
                } else {
                    content.style.maxHeight = content.scrollHeight + 'px';
                    icon.textContent = '−';
                    icon.style.transform = 'rotate(180deg)';
                }
            });
        }
    });
}

function bootstrapLandingPage() {
    initFaqAccordion();
}

document.addEventListener('DOMContentLoaded', bootstrapLandingPage);
