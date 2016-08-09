<?php header('Access-Control-Allow-Origin: *'); ?>
<!DOCTYPE html>
<html>
	<head>
		<title>PokeFinder - Sapuraizu</title>
		<meta charset="UTF-8"/>

		<!-- CSS -->
		<link rel="stylesheet" href="css/bootstrap/bootstrap.min.css"/>
		<link rel="stylesheet" href="css/bootstrap/bootstrap-theme.min.css"/>
		<link rel="stylesheet" href="css/fontawesome/fontawesome.min.css"/>
		<link rel="stylesheet" href="css/animate/animate.css"/>
		<link rel="stylesheet" href="css/style.css"/>

		<!-- Plugins -->
		<link rel="stylesheet" href="css/leaflet.css"/>
		<link rel="stylesheet" href="css/toastr.min.css"/>
	</head>
	<body>
		<div class="container">
			<div class="row">
				<div class="col-md-3 side-left">
					<table class="table table-hover poke-list"><tbody></tbody></table>
				</div>
				<div class="col-md-9 side-right">
					<div id="map"></div>
				</div>
			</div>
		</div>

		<div class="don">
			<span>Faire un don à <strong>Sapuraizu</strong></span>
			<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_blank">
				<input type="hidden" name="cmd" value="_s-xclick">
				<input type="hidden" name="hosted_button_id" value="TNKUGMUPTGGCQ">
				<input type="image" src="https://www.paypalobjects.com/fr_FR/FR/i/btn/btn_donate_SM.gif" border="0" name="submit" alt="PayPal, le réflexe sécurité pour payer en ligne">
				<img alt="" border="0" src="https://www.paypalobjects.com/fr_FR/i/scr/pixel.gif" width="1" height="1">
			</form>
		</div>

		<!-- JavaScript -->
		<script type="text/javascript" src="js/jquery-3.1.0.min.js"></script>
		<script type="text/javascript" src="js/bootstrap/bootstrap.min.js"></script>
		<script type="text/javascript" src="js/javascript.js"></script>
		<script type="text/javascript" src="js/toastr.min.js"></script>

		<!-- Leaflet -->
		<script type="text/javascript" src="js/leaflet.js"></script>
		<script>
			var pokeMap = L.map('map').setView([48.8461233, 2.2191471], 11);
			L.tileLayer('https://api.tiles.mapbox.com/v4/{id}/{z}/{x}/{y}.png?access_token={accessToken}', {
			    attribution: 'Map data &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors, <a href="http://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, Imagery © <a href="http://mapbox.com">Mapbox</a>',
			    maxZoom: 18,
			    id: 'sapuraizu.131iajn9',
			    accessToken: 'pk.eyJ1Ijoic2FwdXJhaXp1IiwiYSI6ImNpcm50YjRqYjAwNmZoa20xaGFoZjJ1ZG8ifQ.FoJY4f1jaqL9fBcqSrdDDw'
			}).addTo(pokeMap);

			$(document).on('click', 'tr', function(){
				var _this = $(this),
					lat = _this.attr('data-lat'),
					lng = _this.attr('data-lng'),
					name = _this.attr('data-name'),
					img = _this.attr('data-picture'),
					verified = _this.attr('data-verified'),
					verifiedText = (verified == "true") ? '<span class="text-green"><i class="fa fa-check"></i> Vérifié</span>' : '<span class="text-orange">Non vérifié</span>';
				var marker = L.marker([lat, lng]).addTo(pokeMap);
				marker.bindPopup('<div class="text-center"><img src="'+ img +'" alt="Image" width="50px"/><br/><strong>'+ name +'</strong><br/><span>'+ lat +', '+ lng +'</span><br/><strong>'+ verifiedText +'</strong></div>').openPopup();
				pokeMap.panTo(new L.LatLng(lat, lng));
			})
		</script>

		<!-- Socket IO -->
		<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/socket.io/1.4.8/socket.io.min.js"></script>
		<script>
			var socket_first = io.connect('188.165.224.208:49001/pokes'),
				socket_second = io.connect('//pokezz.com:80/', {
					transport: ["websocket"]
				}),
				pokemonsAlreadySniffed = [],
				pokemonsImgReplace = {
					'mrmime':'mr-mime',
					'farfetch\'d':'farfetchd'
				};

			//Premier socket
			socket_first.on('connect', function(){
				//toastr.success('Connexion au premier serveur réussie.');
				console.clear();
				console.log('%cConnecté au premier serveur.', 'color: #16a085');
			}).on('disconnect', function(){
				//toastr.warning('Vous avez été deconnecté du premier serveur.');
				console.clear();
				console.log('%cDeconnecté du premier serveur.', 'color: #c0392b');
			}).on('helo', function(data){
				$.each(data, function(index, value){
					newPokemon(value);
				})
			}).on('poke', function(data){
				newPokemon(data);
			})

			//Second socket
			socket_second.on('connect', function(){
				//toastr.success('Connexion au second serveur réussie.');
				console.clear();
				console.log('%cConnecté au second serveur.', 'color: #16a085');
			}).on('disconnect', function(){
				//toastr.warning('Vous avez été deconnecté du second serveur.');
				console.clear();
				console.log('%cDeconnecté du second serveur.', 'color: #c0392b');
			}).on('pokemons', function(data){
				$.each(data, function(index, value){
					newPokemon(value);
				})
			}).on('pokemon', function(data){
				newPokemon(data);
			})

			function newPokemon(data){
				var result = "",
					iv = data.IV || data.iv,
					cIV = (iv != null || iv != '-' || iv != undefined) ? ' ('+iv+'%) ' : '?',
					lat = data.lat,
					lng = data.lon || data.lng,
					timeToRemove = data.time || 0,
					verified = data.verified || false,
					verifiedMessage = (verified === true) ? '<span class="text-green"><i class="fa fa-check"></i> Vérifié</span>' : '<span class="text-orange">Non vérifié</span>',
					imgName = data.name.toLowerCase();
					if(pokemonsImgReplace[imgName] != undefined)
						imgName = data.name.toLowerCase().replace(data.name.toLowerCase(), pokemonsImgReplace[data.name.toLowerCase()]);
					var imgPath = 'https://img.pokemondb.net/artwork/'+ imgName +'.jpg';
				if(pokemonsAlreadySniffed.indexOf(lat+'|'+lng) == -1){
					pokemonsAlreadySniffed.push(lat+'|'+lng);
					result += '<tr class="animated fadeIn" data-verified="'+ verified +'" data-lat="'+ lat +'" data-lng="'+ lng +'" data-name="'+ data.name +'" data-picture="'+ imgPath +'">';
					result += '<td class="poke-icon"><div class="img" style="background-image: url('+ imgPath +');"></div></td>';
					result += '<td class="poke-infos">';
					result += '<div class="poke-name">'+ data.name + cIV +'</div>';
					result += '<div class="poke-coords"><i class="fa fa-map-marker"></i> '+ lat +', '+ lng +'</div>';
					result += '<div class="poke-state">'+ verifiedMessage +'</div>';
					result += '</td>';
					result += '</tr>';
					$('.poke-list').prepend(result);
				}
			}
		</script>
	</body>
</html>