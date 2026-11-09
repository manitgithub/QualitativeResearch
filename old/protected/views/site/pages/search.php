    <script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.5.8/angular.min.js" ></script>

<?php // $this->layout = 'noTheme';?>

	
<div ng-app="myApp" ng-controller="myCtrl">
          
               
                <script>
                var app = angular.module('myApp', []);
                app.controller('myCtrl', function($scope,$http) {
                    $scope.name = "John Doe";
					$http.get("index.php?r=site/page&view=json1&id=<?php echo $_GET[id];?>").success(function (response) {$scope.driver = response.drivers; });
	
	//$http.get("http://www.pd-it-solution.com/backoffice/model/apps_json_chat_room.php?data=chatRoom")
    //.success(function (response) {$scope.driver = response.drivers; });  
					
                });
                </script>

                            <div class="panel-body">
                         
     ค้นหาจากทุกที่  <input type="text" class="form-control" ng-model="searchText" style="width:300px;" placeHolder="ค้นหา" ><br><br>
                            
                            
<div class="row">
	<div class="col-md-12" ng-repeat="user in driver |  filter:searchText">
           <div class="box box-success">
            <div class="box-body">
                              <strong></strong> 
                              <div><strong>ผู้ตอบ</strong> : {{user.text2}}</div>
                              <div><strong>คำถาม</strong> : {{user.text}}</div>
                              <!--<div><strong>รายละเอียด</strong> : {{user.detail}}</div>
                              <div><strong>แจ้งไปยัง</strong> : {{user.type}}</div>-->
                              <div><strong>คำตอบ : </strong>{{user.value}}</div>
                              <div><strong>ตีความ : </strong>{{user.name}}</div>
                              <div>
                              </div>
                              
                              
                              
                              
                                    
								
                              </div>
	</div>
	</div>                   
    
