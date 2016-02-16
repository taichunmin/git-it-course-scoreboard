<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
	<title>git-it scoreboard</title>
	<!-- Latest compiled and minified CSS -->
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" integrity="sha384-1q8mTJOASx8j1Au+a5WDVnPi2lkFfwwEAa8hDDdjZlpLegxhjVME1fgjWPGmkzs7" crossorigin="anonymous">

	<!-- Optional theme -->
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap-theme.min.css" integrity="sha384-fLW2N01lMqjakBkx3l/M9EahuwpSfeNvV63J5ezn3uZzapT0u7EYsXMjQV+0En5r" crossorigin="anonymous">

	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css">

	<!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
	<!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
	<!--[if lt IE 9]>
		<script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
		<script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
	<![endif]-->
</head>
<body>
	<div class="container">
		<h1 class="text-center">git-it scoreboard</h1>
		<div class="table-responsive">
			<table class="table table-striped table-hover table-condensed table-bordered text-center">
				<thead>
					<tr>
						<th class="text-center"><i class="fa fa-male" style="visibility: hidden;"></i></th>
						<th class="text-center">name</th>
						<th class="text-center">github</th>
						<th class="text-center">ssh</th>
						<th class="text-center">completed</th>
@for ($i = 1; $i <= 11; $i++)
						<th class="text-center" style="width:1%" title="{{ $problems[$i-1] }}">{{ $i }}</th>
@endfor
					</tr>
				</thead>
				<tbody>
@forelse( $users as $user )
					<tr>
						<td>{!! empty($user['owned']) ? '' : '<i class="fa fa-male"></i>' !!}</td>
						<td title="{{ $user['mid'] }}">{{ $user['name'] }}</td>
	@if (!empty( $user['github'] ))
						<td><a target="_blank" href="https://github.com/{{ $user['github'] }}">{{ $user['github'] }}</a></td>
	@else
						<td>&nbsp;</td>
	@endif
						<td>{{ $user['port'] or ' ' }}</td>
						<td>{{ count($user['completed']) }}</td>
	@for ($i = 1; $i <= 11; $i++)
		@if (in_array($problems[$i-1], $user['completed']))
						<td class="success"><i class="fa fa-check-circle-o"></i></td>
		@else
						<td>&nbsp;</td>
		@endif
	@endfor
					</tr>
@empty
					<tr>
						<td colspan="16">no data</td>
					</tr>
@endforelse
				</tbody>
			</table>
		</div>
	</div>
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
	<!-- Latest compiled and minified JavaScript -->
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js" integrity="sha384-0mSbJDEHialfmuBBQP6A4Qrprq5OVfW37PRR3j5ELqxss1yVqOtnepnHVP9aJ7xS" crossorigin="anonymous"></script>
</body>
</html>
