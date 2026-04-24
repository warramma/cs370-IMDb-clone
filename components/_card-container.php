<div class="card-container"
     <div class="card">...</div>
    <div class="card">...</div>

<?php foreach ($team_members as $member): ?>
    <?php
    $name = $member['name'];
    $body = $member['body'];
    $image = $member['image'];
    include 'components/_card.php';
    ?>
<?php endforeach; ?>

</div>