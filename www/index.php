<?php
/**
 * 
 * Original project: Katy Nicholson - https://github.com/CoasterKaty
 *
 */

//
$error = false;
$errorMessage = null;
$resultRole = null;

//
if (! file_exists('../inc/config.inc'))
{
	$error = true;
    $errorMessage = 'The <strong>config.inc</strong> file does not exist.';
}
else
{
	//
	include '../inc/auth.php';
	include '../inc/graph.php';

	//
	$auth = new modAuth();
	$graph = new modGraph();

	//
	$photo = $graph->getPhoto();
	$profile = $graph->getProfile();

	$displayName = $profile->displayName;

	foreach ($auth->userRoles as $role)
	{
		$resultRole .= '<li>' . $role . '</li>';
	}
}

?>
<!doctype html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
		<link rel="stylesheet" href="style.css">
		<title>PHP Azure AD OAuth 2.0</title>
	</head>
	<body>
		<header>
			<nav class="navbar navbar-expand-md navbar-dark fixed-top bg-dark">
				<div class="container-fluid">
					<a class="navbar-brand" href="#">PHP Azure AD OAuth 2.0</a>
					<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse" aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
						<span class="navbar-toggler-icon"></span>
					</button>
<?php if ($error !== true): ?>
					<a class="btn btn-primary" href="/?action=logout" role="button">Logout</a>
<?php endif ?>
				</div>
			</nav>
		</header>

		<main class="flex-shrink-0">
			<div class="container">
<?php if ($error !== true): ?>
				<h1><?= $displayName ?></h1>

				<?= $photo ?>

				<h2>Your roles in this app are:</h2>
				<ul>
					<?= $resultRole ?>
				</ul>

				<h2>Profile Graph API output:</h2>
				<pre><?= print_r($profile) ?></pre>
<?php else: ?>
				<div class="alert alert-danger" role="alert">
					<?= $errorMessage ?>
				</div>
<?php endif ?>
			</div>
		</main>

		<footer class="footer mt-auto py-3 bg-light">
			<div class="container">
				<span class="text-muted">Place sticky footer content here.</span>
			</div>
		</footer>		

		<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous"></script>
	</body>
</html>