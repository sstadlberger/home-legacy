<?php

require_once('lib/config.php');
require_once('lib/db.php');

$settings = new ActuatorSettings;

if (isset($_GET['debug'])) {
	echo '<html ng-app="ionicApp">';
} else {
	echo '<html ng-app="ionicApp" manifest="bj.manifest">';
}
?>

	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="initial-scale=1, maximum-scale=1, user-scalable=no, width=device-width">
		<meta name="apple-mobile-web-app-capable" content="yes">
		<meta name="apple-mobile-web-app-status-bar-style" content="default">
		<meta name="apple-mobile-web-app-title" content="Home">
		<!-- all icons are actually in /assets and internally redirected with 
		mod_rewrite in .htaccess -->
		<link rel="apple-touch-icon" sizes="57x57" href="apple-icon-57x57.png" />
		<link rel="apple-touch-icon" sizes="72x72" href="apple-icon-72x72.png" />
		<link rel="apple-touch-icon" sizes="114x114" href="apple-icon-114x114.png" />
		<link rel="apple-touch-icon" sizes="144x144" href="apple-icon-144x144.png" />
		<link rel="icon" type="image/png" sizes="32x32" href="favicon-32x32.png">
		<link rel="icon" type="image/png" sizes="96x96" href="favicon-96x96.png">
		<link rel="icon" type="image/png" sizes="16x16" href="favicon-16x16.png">
		<title>Home</title>
		<link href="ionic/css/ionic.min.css" rel="stylesheet">
		<style>
			.item-divider .item-content {
				padding-top: 0;
				padding-bottom: 0;
				background: none;
			}
			ion-option-button .icon {
				padding-left: 5px;
			}
			ion-option-button .ion-ios-lightbulb, ion-option-button .ion-ios-lightbulb-outline, ion-option-button .ion-ios-arrow-up, ion-option-button .ion-ios-arrow-down {
				padding-left: 7px;
			}
			.center{
				display: -webkit-box;
				display: -moz-box;
				display: -ms-flexbox;
				display: -webkit-flex;
				display: flex;
				-webkit-box-direction: normal;
				-moz-box-direction: normal;
				-webkit-box-orient: horizontal;
				-moz-box-orient: horizontal;
				-webkit-flex-direction: row;
				-ms-flex-direction: row;
				flex-direction: row;
				-webkit-flex-wrap: nowrap;
				-ms-flex-wrap: nowrap;
				flex-wrap: nowrap;
				-webkit-box-pack: center;
				-moz-box-pack: center;
				-webkit-justify-content: center;
				-ms-flex-pack: center;
				justify-content: center;
				-webkit-align-content: stretch;
				-ms-flex-line-pack: stretch;
				align-content: stretch;
				-webkit-box-align: center;
				-moz-box-align: center;
				-webkit-align-items: center;
				-ms-flex-align: center;
				align-items: center;
				text-align: center;
			}
		</style>
		<script src="ionic/js/ionic.bundle.min.js"></script>
		<script type="text/javascript">
			var settings = <?php echo $settings->getSettingsJSON(); ?>;
			
			angular.module('ionicApp', ['ionic'])
			.factory('helper', ['$http', function($http) {
				var pingSysAP = function () {
					return $http.get(
							'ping.php',
							{
								timeout: 5000
							}
						).then(function(response) {    
						return response.data;
					});
				};
				var badge = function (response) {
					if (response == 1) {
						return '';
					} else {
						return '!';
					}
				};
				var icon = function (response) {
					if (response == 0) {
						return noPing('icon');
					} else if (response == 1) {
						return OK('icon');
					} else {
						return noConnection('icon');
					}
				};
				var text = function (response) {
					if (response == 0) {
						return noPing();
					} else if (response == 1) {
						return OK();
					} else {
						return noConnection();
					}
				};
				var OK = function (what) {
					if (what == 'icon') {
						return '✅';
					} else {
						return 'SysAP erreichbar';
					}
				};
				var noPing = function (what) {
					if (what == 'icon') {
						return '🚫';
					} else {
						return 'SysAP down';
					}
				};
				var noConnection = function (what) {
					if (what == 'icon') {
						return '⚠️';
					} else {
						return 'keine Netzwerkverbindung';
					}
				};

				return {
					pingSysAP: pingSysAP,
					badge: badge,
					icon: icon,
					text: text,
					OK: OK,
					noPing: noPing,
					noConnection: noConnection
				};
			}])
			.config(function($stateProvider, $urlRouterProvider) {
				$stateProvider
					.state('tabs', {
						abstract: true,
						templateUrl: "templates/tabs.html",
					})
<?php
	foreach ($settings->getSettings() as $id => $floor) {
		$singlefloor = (count($floor['rooms']) == 1 && $floor['rooms'][0]['name'] == $floor['name']);
		$url = $singlefloor ? '#/room/' . $id . '/0' : '#/floor/' . $id;
?>
					.state('tabs.floor<?php echo $id; ?>', {
						url: '/floor/{floorid:[<?php echo $id; ?>]}',
						views: {
							'floor-<?php echo $id; ?>': {
								templateUrl: 'templates/<?php echo $singlefloor ? "room" : "floor" ?>.html',
								controller: '<?php echo $singlefloor ? "room" : "floor" ?>Ctrl'
							}
						}
					})
<?php	if (!$singlefloor) { ?>
					.state('tabs.room<?php echo $id; ?>', {
						url: "/room/{floorid:[<?php echo $id; ?>]}/{roomid:int}",
						views: {
							'floor-<?php echo $id; ?>': {
								templateUrl: "templates/room.html",
								controller: 'roomCtrl'
							}
						}
					})
<?php
		}
	} 
?>					.state('tabs.status', {
						url: "/status",
						views: {
							'status': {
								templateUrl: "templates/status.html",
								controller: 'statusCtrl'
							}
						}
					});

				$urlRouterProvider.otherwise("/floor/0");

			})
			.directive('clickForOptionsWrapper', [function() {
				return {
					restrict: 'A',
					controller: function($scope) {
						this.closeOptions = function() {
							$scope.$broadcast('closeOptions');
						}
					}
				};
			}])
			.directive('clickForOptions', ['$ionicGesture', function($ionicGesture) {
				return {
					restrict: 'A',
					scope: false,
					require: '^clickForOptionsWrapper',
					link: function (scope, element, attrs, parentController) {
						// A basic variable that determines wether the element was currently clicked
						var clicked;

						// Set an initial attribute for the show state
						attrs.$set('optionButtons', 'hidden');

						// Grab the content
						var content = element[0].querySelector('.item-content');

						// Grab the buttons and their width
						var buttons = element[0].querySelector('.item-options');			

						var closeAll = function() {
							element.parent()[0].$set('optionButtons', 'show');
						};

						// Add a listener for the broadcast event from the parent directive to close
						var previouslyOpenedElement;
						scope.$on('closeOptions', function() {
							if (!clicked) {
								attrs.$set('optionButtons', 'hidden');
							}
						});

						// Function to show the options
						var showOptions = function() {
							// close all potentially opened items first
							parentController.closeOptions();

							var buttonsWidth = buttons.offsetWidth;
							ionic.requestAnimationFrame(function() {
								// Add the transition settings to the content
								content.style[ionic.CSS.TRANSITION] = 'all ease-out .25s';

								// Make the buttons visible and animate the content to the left
								buttons.classList.remove('invisible');
								content.style[ionic.CSS.TRANSFORM] = 'translate3d(-' + buttonsWidth + 'px, 0, 0)';

								// Remove the transition settings from the content
								// And set the "clicked" variable to false
								setTimeout(function() {
									content.style[ionic.CSS.TRANSITION] = '';
									clicked = false;
								}, 250);
							});		
						};

						// Function to hide the options
						var hideOptions = function() {
							var buttonsWidth = buttons.offsetWidth;
							ionic.requestAnimationFrame(function() {
								// Add the transition settings to the content
								content.style[ionic.CSS.TRANSITION] = 'all ease-out .25s';

								// Move the content back to the original position
								content.style[ionic.CSS.TRANSFORM] = '';
					
								// Make the buttons invisible again
								// And remove the transition settings from the content
								setTimeout(function() {
									buttons.classList.add('invisible');
									content.style[ionic.CSS.TRANSITION] = '';
								}, 250);				
							});
						};

						// Watch the open attribute for changes and call the corresponding function
						attrs.$observe('optionButtons', function(value){
							if (value == 'show') {
								showOptions();
							} else {
								hideOptions();
							}
						});

						// Change the open attribute on tap
						$ionicGesture.on('tap', function(e){
							clicked = true;
							if (attrs.optionButtons == 'show') {
								attrs.$set('optionButtons', 'hidden');
							} else {
								attrs.$set('optionButtons', 'show');
							}
						}, element);
					}
				};
			}])
			.controller('floorCtrl', ['$scope', '$stateParams', function($scope, $stateParams) {
				$scope.floor = settings[$stateParams.floorid];
				$scope.floor.id = $stateParams.floorid;
			}])
			.controller('roomCtrl', ['$scope', '$rootScope', '$stateParams', '$http', 'helper', function($scope, $rootScope, $stateParams, $http, helper) {
				var roomid = $stateParams.roomid ? $stateParams.roomid : 0;
				$scope.room = settings[$stateParams.floorid].rooms[roomid];
				$scope.command = function(type, actuator, channel, command) {
					var params = '?type=' + type;
					params += '&actuator=' + actuator;
					params += '&channel=' + channel;
					params += '&command=' + command;
					$http.get(
						'bj.php' + params,
						{
							timeout: 5000
						}
					).then(function (response) {
						// AOK
					}, function () {
						$rootScope.status = helper.badge();
					});
				};
			}])
			.controller('statusTabCtrl', ['$rootScope', 'helper', function($rootScope, helper) {
				helper.pingSysAP().then(function(data) {
					$rootScope.status = helper.badge(data);
				}, function() {
					$rootScope.status = helper.badge();
				});
			}])
			.controller('statusCtrl', ['$scope', '$rootScope', '$ionicLoading', 'helper', function($scope, $rootScope, $ionicLoading, helper) {
				$ionicLoading.show({
					template: 'Lade...'
				});
				var updateBadge = function () {
					helper.pingSysAP().then(function(data) {
						$rootScope.status = helper.badge(data);
						$scope.statusIcon = helper.icon(data);
						$scope.statusText = helper.text(data);
						$ionicLoading.hide();
						$scope.$broadcast('scroll.refreshComplete');
					}, function() {
						$rootScope.status = helper.badge();
						$scope.statusIcon = helper.icon();
						$scope.statusText = helper.text();
						$ionicLoading.hide();
						$scope.$broadcast('scroll.refreshComplete');
					});
				};
				$scope.doPing = function () {
					updateBadge();
				};
				updateBadge();
			}]);

		</script>
	</head>
	<body>
		<ion-nav-bar class="bar-stable">
			<ion-nav-back-button>
			</ion-nav-back-button>
		</ion-nav-bar>
		<ion-nav-view></ion-nav-view>
		<script id="templates/tabs.html" type="text/ng-template">
			<ion-tabs class="tabs-icon-top tabs-color-positive">
<?php
	foreach ($settings->getSettings() as $id => $floor) {
?>				<ion-tab title="<?php echo $floor['name']; ?>" icon-on="<?php echo $floor['icon']; ?>" icon-off="<?php echo $floor['icon']; ?>-outline" href="#floor/<?php echo $id; ?>">
					<ion-nav-view name="floor-<?php echo $id; ?>"></ion-nav-view>
				</ion-tab>
<?php
	}
?>				<ion-tab ng-controller="statusTabCtrl" title="Status" icon-on="ion-ios-world" icon-off="ion-ios-world-outline" href="#status" class="tabs-energized" badge="$root.status" badge-style="badge-assertive">
					<ion-nav-view name="status"></ion-nav-view>
				</ion-tab>
			</ion-tabs>
		</script>
		<script id="templates/floor.html" type="text/ng-template">
			<ion-view view-title="{{floor.name}}">
				<ion-content>
					<ion-list>
						<ion-item class="item-icon-right" href="#/room/{{floor.id}}/{{id}}" ng-repeat="(id, room) in floor.rooms">
							{{room.name}} <span class="icon ion-ios-arrow-right"></span>
						</ion-item>
					</ion-list>
				</ion-content>
			</ion-view>
		</script>
		<script id="templates/room.html" type="text/ng-template">
			<ion-view view-title="{{room.name}}">
				<ion-content>
					<ion-list click-for-options-wrapper>
						<ion-item click-for-options ng-repeat="(id, actuator) in room.actuators" ng-class="actuator.type == 'divider' ? 'item-divider' : ''">
							{{actuator.name}}
							
							<!-- buzzer -->
							<ion-option-button class="button-balanced" on-touch="command('setSwitch', actuator.actuator, actuator.channel, 'on')" on-release="command('setSwitch', actuator.actuator, actuator.channel, 'off')" ng-if="actuator.type == 'buzzer'">
								<span class="icon ion-ios-unlocked"></span>
							</ion-option-button>
							
							<!-- scene -->
							<ion-option-button class="button-balanced" on-touch="command('setScene', actuator.actuator, actuator.channel, 'set')" ng-if="actuator.type == 'scene'">
								<span class="icon ion-film-marker"></span>
							</ion-option-button>
							
							<!-- dimmer -->
							<ion-option-button class="button-stable" on-touch="command('setDimmer', actuator.actuator, actuator.channel, 'up')" on-release="command('setDimmer', actuator.actuator, actuator.channel, 'stop')" ng-if="actuator.type == 'dimmer'">
								<span class="icon ion-ios-plus-outline"></span>
							</ion-option-button>
							<ion-option-button class="button-stable" on-touch="command('setDimmer', actuator.actuator, actuator.channel, 'down')" on-release="command('setDimmer', actuator.actuator, actuator.channel, 'stop')" ng-if="actuator.type == 'dimmer'">
								<span class="icon ion-ios-minus-outline"></span>
							</ion-option-button>
							<ion-option-button class="button-energized" on-touch="command('setDimmer', actuator.actuator, actuator.channel, 'on')" ng-if="actuator.type == 'dimmer'">
								<span class="icon ion-ios-lightbulb"></span>
							</ion-option-button>
							<ion-option-button class="button-dark" on-touch="command('setDimmer', actuator.actuator, actuator.channel, 'off')" ng-if="actuator.type == 'dimmer'">
								<span class="icon ion-ios-lightbulb-outline"></span>
							</ion-option-button>
							
							<!-- switch -->
							<ion-option-button class="button-balanced" on-touch="command('setSwitch', actuator.actuator, actuator.channel, 'on')" ng-if="actuator.type == 'switch'">
								<span class="icon ion-ios-circle-filled"></span>
							</ion-option-button>
							<ion-option-button class="button-assertive" on-touch="command('setSwitch', actuator.actuator, actuator.channel, 'off')" ng-if="actuator.type == 'switch'">
								<span class="icon ion-ios-circle-outline"></span>
							</ion-option-button>
							
							<!-- light -->
							<ion-option-button class="button-energized" on-touch="command('setSwitch', actuator.actuator, actuator.channel, 'on')" ng-if="actuator.type == 'light'">
								<span class="icon ion-ios-lightbulb"></span>
							</ion-option-button>
							<ion-option-button class="button-dark" on-touch="command('setSwitch', actuator.actuator, actuator.channel, 'off')" ng-if="actuator.type == 'light'">
								<span class="icon ion-ios-lightbulb-outline"></span>
							</ion-option-button>
							
							<!-- light-group -->
							<ion-option-button class="button-energized" on-touch="command('setSwitchGroup', actuator.actuator, actuator.channel, 'on')" ng-if="actuator.type == 'light-group'">
								<span class="icon ion-ios-lightbulb"></span>
							</ion-option-button>
							<ion-option-button class="button-dark" on-touch="command('setSwitchGroup', actuator.actuator, actuator.channel, 'off')" ng-if="actuator.type == 'light-group'">
								<span class="icon ion-ios-lightbulb-outline"></span>
							</ion-option-button>
							
							<!-- shutter -->
							<ion-option-button class="button-stable" on-touch="command('setShutter', actuator.actuator, actuator.channel, 'up')" on-release="command('setShutter', actuator.actuator, actuator.channel, 'stop')" ng-if="actuator.type == 'shutter'">
								<span class="icon ion-ios-plus-outline"></span>
							</ion-option-button>
							<ion-option-button class="button-stable" on-touch="command('setShutter', actuator.actuator, actuator.channel, 'down')" on-release="command('setShutter', actuator.actuator, actuator.channel, 'stop')" ng-if="actuator.type == 'shutter'">
								<span class="icon ion-ios-minus-outline"></span>
							</ion-option-button>
							<ion-option-button class="button-positive" on-touch="command('setShutter', actuator.actuator, actuator.channel, 'up')" ng-if="actuator.type == 'shutter'">
								<span class="icon ion-ios-arrow-up"></span>
							</ion-option-button>
							<ion-option-button class="button-dark" on-touch="command('setShutter', actuator.actuator, actuator.channel, 'down')" ng-if="actuator.type == 'shutter'">
								<span class="icon ion-ios-arrow-down"></span>
							</ion-option-button>
							<ion-option-button class="button-assertive" on-touch="command('setShutter', actuator.actuator, actuator.channel, 'stop')" ng-if="actuator.type == 'shutter'">
								<span class="icon ion-ios-close-outline"></span>
							</ion-option-button>
							
							<!-- shutter-group -->
							<ion-option-button class="button-stable" on-touch="command('setShutterGroup', actuator.actuator, actuator.channel, 'up')" on-release="command('setShutterGroup', actuator.actuator, actuator.channel, 'stop')" ng-if="actuator.type == 'shutter-group'">
								<span class="icon ion-ios-plus-outline"></span>
							</ion-option-button>
							<ion-option-button class="button-stable" on-touch="command('setShutterGroup', actuator.actuator, actuator.channel, 'down')" on-release="command('setShutterGroup', actuator.actuator, actuator.channel, 'stop')" ng-if="actuator.type == 'shutter-group'">
								<span class="icon ion-ios-minus-outline"></span>
							</ion-option-button>
							<ion-option-button class="button-positive" on-touch="command('setShutterGroup', actuator.actuator, actuator.channel, 'up')" ng-if="actuator.type == 'shutter-group'">
								<span class="icon ion-ios-arrow-up"></span>
							</ion-option-button>
							<ion-option-button class="button-dark" on-touch="command('setShutterGroup', actuator.actuator, actuator.channel, 'down')" ng-if="actuator.type == 'shutter-group'">
								<span class="icon ion-ios-arrow-down"></span>
							</ion-option-button>
							<ion-option-button class="button-assertive" on-touch="command('setShutterGroup', actuator.actuator, actuator.channel, 'stop')" ng-if="actuator.type == 'shutter-group'">
								<span class="icon ion-ios-close-outline"></span>
							</ion-option-button>
							
						</ion-item>
					</ion-list>
				</ion-content>
			</ion-view>
		</script>
		<script id="templates/status.html" type="text/ng-template">
			<ion-view view-title="Status" cache-view="false">
				<ion-content class="center">
					<ion-refresher pulling-text="Aktualisieren…" on-refresh="doPing()">
					</ion-refresher>
					<span style="font-size: 72px; line-height: 72px;">{{statusIcon}}</span><br />
					{{statusText}}
				</ion-content>
			</ion-view>
		</script>
	</body>
</html>
