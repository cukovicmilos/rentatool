<?php
/**
 * Cancel Reservation Page
 */

$code = get('code', '');

if (empty($code)) {
    redirect('');
}

// Get reservation
$reservation = db()->fetch("SELECT * FROM reservations WHERE reservation_code = ?", [$code]);

if (!$reservation) {
    flash('error', 'Rezervacija nije pronađena.');
    redirect('');
}

// Check if already cancelled
if ($reservation['status'] === 'cancelled') {
    flash('info', 'Ova rezervacija je već otkazana.');
    redirect('rezervacija/' . $code);
}

// Check if completed
if ($reservation['status'] === 'completed') {
    flash('error', 'Završena rezervacija ne može biti otkazana.');
    redirect('rezervacija/' . $code);
}

// Check if can be cancelled (min 2 days before start)
$daysUntilStart = (strtotime($reservation['date_start']) - strtotime('today')) / 86400;
if ($daysUntilStart < MIN_CANCEL_DAYS) {
    flash('error', 'Otkazivanje nije moguće manje od ' . MIN_CANCEL_DAYS . ' dana pre početka rezervacije.');
    redirect('rezervacija/' . $code);
}

// Cancel the reservation
db()->execute("
    UPDATE reservations 
    SET status = 'cancelled', cancelled_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP 
    WHERE id = ?
", [$reservation['id']]);

flash('success', 'Rezervacija je uspešno otkazana.');
redirect('rezervacija/' . $code);
