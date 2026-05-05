<?php include_once("components/_header.php"); ?>
<div>
    <div class="p-5 bg-dark">
        <h1 class="text-light">IMDB Pirated</h1>
        <p class="text-light">Our database of movies is far better than those on the other side.</p>
    </div>
</div>

<?php
// Test data
$team_members = [
        ["name" => "Lava Chicken", "body" => "fwq", "image" => "assets/lalalalava.jpg"],
        ["name" => "Chicken Jockey", "body" => "dwqd", "image" => "assets/lalalalava.jpg"],
        ["name" => "I am Steve", "body" => "dwqd", "image" => "assets/lalalalava.jpg"]
];
?>

<div class="card-container">
    <?php foreach ($team_members as $member): ?>
        <?php
        $name = $member['name'];
        $body = $member['body'];
        $image = $member['image'];
        include 'components/_card.php';
        ?>
    <?php endforeach; ?>
</div>

<?php include_once("components/_footer.php"); ?>
