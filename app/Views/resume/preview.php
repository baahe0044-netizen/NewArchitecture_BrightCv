<?php

$name = $_POST['fullname'] ?? '';
$email = $_POST['email'] ?? '';
$summary = $_POST['summary'] ?? '';

?>

<h2 class="text-center text-2xl font-bold">
    <?= htmlspecialchars($name) ?>
</h2>

<p class="text-center text-sm text-gray-600">
    <?= htmlspecialchars($email) ?>
</p>

<hr class="my-4">

<section class="mb-4">
    <h3 class="font-bold text-lg border-b pb-1">
        PROFESSIONAL SUMMARY
    </h3>

    <p class="text-sm text-gray-700 mt-2">
        <?= nl2br(htmlspecialchars($summary)) ?>
    </p>
</section>