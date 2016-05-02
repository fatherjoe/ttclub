var ttclubApp = angular.module('ttclubApp', ['restangular']);

ttclubApp.service('TtclubService', function(Restangular) {
    Restangular.setBaseUrl('index.php?option=com_ttclub&format=json');
    Restangular.setDefaultHttpFields({
        timeout: 30000 /* Millis */
    });

    var ttclubService = {};

    ttclubService.getTeamList = function () {
        return Restangular.allUrl('teams', 'index.php?option=com_ttclub&view=teams&format=json').getList()
    };

    return ttclubService;
});

ttclubApp.controller('TeamCtrl', function($scope, TtclubService, Restangular) {
    TtclubService.getTeamList().then(function successCallback(result) {
        $scope.teams = result;
    }, function errorCallback(reason) {
        $scope.error = true;
    })
});
