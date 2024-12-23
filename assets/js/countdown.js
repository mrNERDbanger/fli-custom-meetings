jQuery(document).ready(function($) {
    function updateCountdown() {
        $('.countdown-timer').each(function() {
            var timestamp = $(this).data('timestamp') * 1000;
            var now = new Date().getTime();
            var distance = timestamp - now;

            if (distance < 0) {
                $(this).html('<div class="meeting-live">Meeting is Live!</div>');
                return;
            }

            var days = Math.floor(distance / (1000 * 60 * 60 * 24));
            var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            var seconds = Math.floor((distance % (1000 * 60)) / 1000);

            $(this).find('.days').text(days.toString().padStart(2, '0'));
            $(this).find('.hours').text(hours.toString().padStart(2, '0'));
            $(this).find('.minutes').text(minutes.toString().padStart(2, '0'));
            $(this).find('.seconds').text(seconds.toString().padStart(2, '0'));
        });
    }

    setInterval(updateCountdown, 1000);
    updateCountdown();
}); 