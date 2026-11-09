<?php $this->layout = 'noThemes';?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta content="IE=edge" http-equiv="X-UA-Compatible">
	<meta content="initial-scale=1.0, maximum-scale=1.0, user-scalable=no, width=device-width" name="viewport">
	<title>Login - Material</title>

	<!-- css -->
	<link href="theme/material/css/base.min.css" rel="stylesheet">
	<link href="theme/material/css/project.min.css" rel="stylesheet">
	<link href="https://fonts.googleapis.com/css?family=Kodchasan" rel="stylesheet">
<style type="text/css">
body{
font-family: 'Kodchasan', sans-serif;
  background: url(images/bg.jpg) no-repeat center center fixed; 
  -webkit-background-size: cover;
  -moz-background-size: cover;
  -o-background-size: cover;
  background-size: cover;


}
</style>
	<!-- favicon -->
	<!-- ... -->
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head>
<body class="page-brand">
	<header class="header header-brand ui-header">
		<span class="header-logo">วิเคราะเชิงคุณภาพ</span>
	</header>
	<main class="content">
		<div class="container">
			<div class="row">
				<div class="col-lg-4 col-lg-push-4 col-sm-6 col-sm-push-3">
					<section class="content-inner">
						<div class="card">
							<div class="card-main">
								<div class="card-header">
									<div class="card-inner">
										<h1 class="card-heading">Login</h1>
									</div>
								</div>
								<div class="card-inner">
									<p class="text-center">
									</p>
<?php $form=$this->beginWidget('CActiveForm', array(
	'id'=>'login-form',
	'enableClientValidation'=>true,
	'clientOptions'=>array(
		'validateOnSubmit'=>true,
	),
)); ?>
										<div class="form-group form-group-label">
											<div class="row">
												<div class="col-md-10 col-md-push-1">
													<label class="floating-label" for="ui_login_username">Username</label>
                                                  		<?php echo $form->textField($model,'username',array('class'=>"form-control")); ?>
  
				</div>
											</div>
										</div>
										<div class="form-group form-group-label">
											<div class="row">
												<div class="col-md-10 col-md-push-1">
													<label class="floating-label" for="ui_login_password">Password</label>
                                             <?php echo $form->passwordField($model,'password',array('class'=>"form-control")); ?>

												</div>
											</div>
										</div>
										<div class="form-group">
											<div class="row">
												<div class="col-md-10 col-md-push-1">
													<button class="btn btn-block btn-brand waves-attach waves-light">Sign In</button>
												</div>
											</div>
										</div>
										<div class="form-group">
											<div class="row">
												<div class="col-md-10 col-md-push-1">
													<div class="checkbox checkbox-adv">
														<label for="ui_login_remember">
															<input class="access-hide" id="ui_login_remember" name="ui_login_remember" type="checkbox">Stay signed in
	<span class="checkbox-circle"></span><span class="checkbox-circle-check"></span><span class="checkbox-circle-icon icon">done</span>
														</label>
													</div>
												</div>
											</div>
										</div>
								<?php $this->endWidget(); ?>

								</div>
							</div>
						</div>
						<div class="clearfix">
							<p class="margin-no-top pull-left"><a class="btn btn-flat btn-brand waves-attach" href="javascript:void(0)">Need help?</a></p>
							<p class="margin-no-top pull-right"><a class="btn btn-flat btn-brand waves-attach" data-toggle="modal" href="index.php?r=userkey/create" data-target="#qution">Create an account</a></p>
						</div>
					</section>
				</div>
			</div>
		</div>
	</main>
	<footer class="ui-footer">
		<div class="container">
			<p>Concept By jurai0902@gmail.com :: Code By manit.pbac@gmail.com 2016</p>
		</div>
	</footer>
	<div aria-hidden="true" class="modal fade" id="qution" role="dialog" tabindex="-1">
		<div class="modal-dialog modal-full">
			<div class="modal-content">
			</div>
		</div>
	</div>

	<!-- js -->
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.0/jquery.min.js"></script>
	<script src="theme/material/js/base.min.js"></script>
	<script src="theme/material/js/project.min.js"></script>
</body>
</html>


</div><!-- form -->
