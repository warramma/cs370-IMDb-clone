<?php include_once("components/_header.php"); ?>
    <div>
        <div class="p-5 bg-dark">
            <h1 class="text-light">IMDB Clone</h1>
            <p class="text-light">This is our project for our SP26 CS 370-01 class with Dr. Adam Byerly.</p>
            <p class="text-light">Team members include: Wardiyah Rammazy (Team Lead), Ellen Abbott, Jace Homberg, Jean-Denis de Beauvoir, Daniel Thornley and Louie Ubert</p>
        </div>
    </div>

<?php
// Test data
$team_members = [ // I hate how this says "team_members", reused code so funny
        ["name" => "Lava Chicken", "body" => "Steve's Lava Chicken yeah its tasty as hell!", "image" => "assets/lalalalava.jpg"],
        ["name" => "Chicken Jockey", "body" => "Crowd goes wild.", "image" => "assets/lalalalava.jpg"],
        ["name" => "I am Steve", "body" => "Jack Black moment", "image" => "assets/lalalalava.jpg"]
];
?>

    <div class="card-grid">
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
