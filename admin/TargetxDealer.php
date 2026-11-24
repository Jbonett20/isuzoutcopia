<?php
$loc = "dealertarget";
include_once '../admin/src/seguridad.php';
@session_start();
include_once("../controllers/dealertarget.php");

$sessionRoleIds = array_map(
    'intval',
    $_SESSION['UsrData']['rolids'] ?? []
);

$esAdmin = Dealertarget::esAdmin();
$esDealerLimitado = Dealertarget::dealerSesion() > 0;

$rolTexto = $esDealerLimitado
    ? 'DEALER (SOLO SU EMPRESA)'
    : 'GENERAL ADMIN';
$dealerSesion = (int)($_SESSION['UsrData']['dealerid'] ?? 0);
$dealerFiltroActual = isset($dealerFiltro) ? (int)$dealerFiltro : 0;

// Para usuario limitado: nombre de su dealer (listadoDealers ya viene filtrado a su dealer)
$dealerNombreLimitado = '';
if ($esDealerLimitado && !empty($listadoDealers)) {
    $dealerNombreLimitado = $listadoDealers[0]['dealer'];
}
?>

<!DOCTYPE html>
<html>

<head>
	<?php require_once("src/header.php"); ?>
	<style>
	.select2.select2-container.select2-container--default .select2-selection.select2-selection--single .select2-selection__rendered,
	.select2.select2-container.select2-container--default .select2-selection.select2-selection--multiple .select2-selection__rendered {
		color: #ffffff;
		text-align: left;
	}

	.select2-container--default .select2-selection--single .select2-selection__placeholder {
		color: #ffffff;
	}

	.form-control {
		color: #ebdfdf;
	}
	</style>
</head>

<body class="">
	<?php require_once("src/navbar.php"); ?>

	<nav id="sidebar" class="sidebar" role="navigation">
		<div class="js-sidebar-content">
			<?php require_once("src/logo.php"); ?>
			<?php require_once("src/sidebar.php"); ?>
		</div>
	</nav>

	<div class="content-wrap">
		<main id="content" class="content" role="main">
			<ol class="breadcrumb">
				<li class="breadcrumb-item">You are in</li>
				<li class="breadcrumb-item active">Target x Dealer</li>
			</ol>

			<div class="row">
				<div class="col-lg-12">
					<section class="widget">
						<header>
							<h6 style="color:black;font-weight:bold;text-align:left;">
								GENERAL FILTERS ( <i class="fa fa-globe"></i> <?php echo $rolTexto; ?> )
							</h6>
						</header>
						<div class="widget-body">
							<div class="row align-items-center">
								<label class="col-auto col-form-label text-black font-weight-bold text-md-left mb-0 pr-1"
									for="dealerFilter">Dealer:</label>
								<div class="col-lg-4 col-md-7 col-sm-10 pl-1 mt-2 mt-lg-0">
									<?php if (!$esDealerLimitado) { ?>
									<select style="width: 100%;" class="form-control" id="dealerFilter" name="dealerFilter">
										<option value="0">ALL DEALERS</option>
										<?php foreach ($listadoDealers as $dealer) { ?>
										<?php
											$selected = '';
											if ($dealerFiltroActual > 0 && (int)$dealer['dealerid'] === $dealerFiltroActual) {
												$selected = 'selected';
											}
										?>
										<option title="<?php echo ($dealer['bandera']); ?>"
											value="<?php echo ($dealer['dealerid']); ?>" <?php echo $selected; ?>>
											<?php echo ($dealer['dealer']); ?>
										</option>
										<?php } ?>
									</select>
									<?php } else { ?>
									<input type="text" class="form-control" id="dealerFilter"
										value="<?php echo htmlspecialchars($dealerNombreLimitado, ENT_QUOTES, 'UTF-8'); ?>"
										disabled>
									<?php } ?>
								</div>
							</div>
						</div>
					</section>
				</div>
			</div>

			<div class="row">
				<div class="col-lg-12" id="TabItems"></div>
			</div>

			<footer class="content-footer hidden-print">
				<?php require_once("src/footer.php"); ?>
			</footer>
		</main>
	</div>

	<?php if ($canManage) { ?>
	<div class="modal fade" id="modalAdd" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
		<div class="modal-dialog modal-dialog-centered" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="exampleModalLongTitle">Target x Dealer</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body" id="ModalBody" style="text-align: center;">
					<form id="frmReg" class="form-horizontal" role="form" autocomplete="off">
						<input type="hidden" name="registroid" id="registroid">

						<div class="form-group row">
							<label class="col-md-3 col-form-label text-white text-md-left" for="dealerid">Dealer</label>
							<div class="col-md-9">
								<?php if (!$esDealerLimitado) { ?>
								<select style="width: 100%;" class="form-control" id="dealerid" name="dealerid"
									required>
									<option value=""></option>
									<?php foreach ($listadoDealers as $dealer) { ?>
									<option title="<?php echo ($dealer['bandera']); ?>"
										value="<?php echo ($dealer['dealerid']); ?>">
										<?php echo ($dealer['dealer']); ?>
									</option>
									<?php } ?>
								</select>
								<?php } else { ?>
								<input type="hidden" name="dealerid" id="dealerid" value="<?php echo $dealerSesion; ?>">
								<input type="text" class="form-control"
									value="<?php echo htmlspecialchars($dealerNombreLimitado, ENT_QUOTES, 'UTF-8'); ?>"
									disabled>
								<?php } ?>
							</div>
						</div>

						<div class="form-group row">
							<label class="col-md-3 col-form-label text-white text-md-left" for="year">Year</label>
							<div class="col-md-9">
								<div class="input-group">
									<span class="input-group-prepend">
										<span class="input-group-text">
											<i class="fa fa-calendar"></i>
										</span>
									</span>
									<input required autocomplete="off" type="number" min="1900" max="2100"
										class="form-control" id="year" name="year" placeholder="Year">
								</div>
							</div>
						</div>

						<div class="form-group row">
							<label class="col-md-3 col-form-label text-white text-md-left" for="objetivo">Target (Units)</label>
							<div class="col-md-9">
								<div class="input-group">
									<span class="input-group-prepend">
										<span class="input-group-text">
											<i class="fa fa-bullseye"></i>
										</span>
									</span>
									<input required autocomplete="off" type="number" min="0" class="form-control"
										id="objetivo" name="objetivo" placeholder="Target (Units)">
								</div>
							</div>
						</div>

						<div class="form-group row">
							<label class="col-md-3 col-form-label text-white text-md-left" for="objetivovlr">Target ($ USD)</label>
							<div class="col-md-9">
								<div class="input-group">
									<span class="input-group-prepend">
										<span class="input-group-text">
											<i class="fa fa-dollar"></i>
										</span>
									</span>
									<input required autocomplete="off" type="number" min="0" class="form-control"
										id="objetivovlr" name="objetivovlr" placeholder="Target ($ USD)">
								</div>
							</div>
						</div>
					</form>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-danger" data-dismiss="modal">Cancel</button>
					<button form="frmReg" type="submit" class="btn btn-success ml-3">Save</button>
				</div>
			</div>
		</div>
	</div>
	<?php } ?>

	<?php require_once("src/scripts.php"); ?>
</body>

</html>
