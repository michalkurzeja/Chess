var polcodeChess = angular.module('polcodeChess', ['ui.bootstrap']);

polcodeChess.config(function($interpolateProvider) {
	$interpolateProvider.startSymbol('{[{').endSymbol('}]}');
})

ModalCtrl = function($scope, $modalInstance) {
	console.log('in ModalCtrl');
	
	$scope.push = function(value) {
		$modalInstance.close(value);
	}
};

polcodeChess.controller('ChessboardCtrl', function($scope, $http, boardFactory, $controller, $modal) {

	$scope.game_id;
	$scope.move_count;
	$scope.player_color;
	$scope.board = [];
	$scope.started;
	$scope.ended;
	$scope.winner;
	$scope.white;
	$scope.black;
	$scope.color;
	
	var $chessboard;
	var highlight = [];
	var selection_highlight = [];
	var last_move_highlight = [];
	var selection = null;
	var turn;
	var awaiting_move_response = false;
	var en_passant_square;
	var en_passant_piece;
	var castle = null;
	
	var modalInstance; 

	$scope.openModal = function(piece, piece_info) {
		modalInstance = $modal.open({
			templateUrl: 'modal.html',
			controller: ModalCtrl,
			backdrop: 'static',
			keyboard: false
		});
		
		modalInstance.result.then(function(new_class) {
			var color = piece.is_white ? 'White' : 'Black';
			
			piece.classname = new_class;
			piece.name = new_class + color;
			console.log(piece);
			
			coords = {x: piece.file, y: piece.rank};
			
			sendMoveRequest(piece_info, coords, new_class);
		});
	}

	$scope.highlightMovesOn = function(piece) {
		if(turn) {
			if( piece.is_white == $scope.player_white ) {
				for(square in piece.moves) {
					highlight[square] = $chessboard
						.find("[data-coords='" + piece.moves[square].x + piece.moves[square].y + "']");
						
					if( $scope.board[ piece.moves[square].y -1 ][ piece.moves[square].x -1 ] ) {
						highlight[square].addClass('move-highlight').addClass('kill-highlight');
					} else {
						highlight[square].addClass('move-highlight');					
					}
				}
			}
		}
	}
	
	$scope.highlightMovesOff = function() {
		for(square in highlight) {
			highlight[square].removeClass('move-highlight').removeClass('kill-highlight');
		}
		highlight = [];
	}
	
	$scope.squareClicked = function(file, rank) {
		if(turn) {
			target = $scope.board[ rank-1 ][ file-1 ];
			console.log(target);
			if( !selection ) {
				/* click on empty square (clear selection) */
				if( !target ) {
					clearSelection();
					return;
				}
				
				/* click on a piece of your color (select) */
				if( target.is_white == $scope.player_white ) {
					select(target);
					return;
				}
				
				return;
			}
			
			/* click on selected square */
			if( selection.file == file && selection.rank == rank) {
				clearSelection();
				return;
			} 
			
			/* click on another piece of the same color (switch selection) */
			if( target && target.is_white == $scope.player_white ) {
				clearSelection();
				select(target);
				return;
			}
			
			/* click on disallowed square with piece selected */
			if( !isMoveLegal( {x: file, y: rank}, selection ) ) {
				clearSelection();
				return;
			}
			
			movePiece(selection, {x: file, y: rank});
		}
	}
	
	$scope.init = function(game_id, move_count, my_turn, color, white, black, ep_square, ep_piece, castle_data, started, ended, winner) {
		$scope.game_id = game_id;
		$scope.move_count = move_count;
		$scope.started = started;
		$scope.ended = ended;
		$scope.winner = getWinnerDisplayName(winner);
		$scope.white = white;
		$scope.black = black;
		turn = my_turn ? true : false;
		en_passant_square = JSON.parse(ep_square);
		en_passant_piece = JSON.parse(ep_piece);
		castle = JSON.parse(castle_data);
		console.log(castle);
		$scope.player_white = color ? true : false;
		$scope.board = boardFactory.getBoardAndPieces($scope.game_id);
		$chessboard = $('#chessboard');
		
		sendUpdateRequest();
		setInterval( sendUpdateRequest, 1000 );
	}
	
	$scope.getPlayerDisplayName = function(white)
	{
		if(white) {
			return $scope.white ? $scope.white : 'Waiting for player...'
		}
		
		return $scope.black ? $scope.black : 'Waiting for player...'
	}
	
	$scope.getTurnColor = function()
	{
		if( turn ) {
			return $scope.player_white ? 'White' : 'Black';
		}
		
		return $scope.player_white ? 'Black' : 'White';
	}
	
	function getWinnerDisplayName(winner_name)
	{
		if($scope.ended && winner_name) {
			winner_color = winner_name == $scope.white ? 'White' : 'Black';
			return winner_name + ' (' + winner_color + ')'; 
		}
		
		return '';
	}
	
	function sendUpdateRequest() {
		$http({method: 'POST', url:  $scope.game_id + '/update', data: {move_count: $scope.move_count}, headers: {'Content-type': 'application/json'}})
			.success( function(data) { update(data); } ).error(function() {
				console.log('Error getting update!');
			});
	}
	
	function update(data) {
		if(!awaiting_move_response) {
			if(typeof data.move_count != 'undefined' && (data.move_count == 0 || data.move_count > $scope.move_count)) {
				console.log('Sync operations:');
				turn = data.turn;
				en_passant_square = data.en_passant_square;
				en_passant_piece = data.en_passant_piece;
				$scope.move_count = data.move_count;		
				updateMovedPiece(data.last_moved, data.captured);
				castle = data.castle;
				refreshPiecesMoves(data.moves);
				console.log('Sync completed');
			}
			
			if(typeof data.white_name != 'undefined') {
				$scope.white = data.white_name;
				$scope.black = data.black_name;
			}
			
			if(typeof data.started != 'undefined') {
				$scope.started = data.started;
			}
			
			if(typeof data.winner != 'undefined') {
				$scope.ended = 1;
				$scope.winner = getWinnerDisplayName(data.winner);
			}
		}
	}
	
	function isPawnOnTheOtherSide(piece)
	{
		if( piece.classname != "Pawn" ) {
			return false;
		}
		
		if( (piece.is_white && piece.rank == 8) ||
			(!piece.is_white && piece.rank == 1) ) {
				return true;
			}
	}
	
	function movePiece(piece, coords) {
		awaiting_move_response = true;
		
		clearSelection();
		
		console.log('movePiece() called with:');
		console.log('piece:');
		console.log(piece);
		console.log('coords: ');
		console.log(coords);
			
		console.log('moving [' + piece.file + ', ' + piece.rank + '] to [' + coords.x + ', ' + coords.y + ']');

		lastMoveHighlight(piece, coords.x, coords.y);
		
		var piece_info = { id: piece.id, file: piece.file, rank: piece.rank };

		updatePieceCoordinates(
						{x: piece.file - 1, y: piece.rank - 1},
						{x: coords.x - 1, y: coords.y - 1}
					);
		
		if( isPawnOnTheOtherSide(piece) ) {
			$scope.openModal(piece, piece_info);
			
			return;
		}
		
		if(en_passant_square && coords.x == en_passant_square.x && coords.y == en_passant_square.y) {
			$scope.board[ en_passant_piece.rank - 1 ][ en_passant_piece.file - 1 ] = null;
			
			en_passant_square = null;
			en_passant_piece = null;
		}
		
		if(castle && castle.my) {
			var cstl = castle.my;
			
			for(var i=0; i<2; i++) {
				var c_sq = cstl[i];
				
				if( c_sq.square.x ==  coords.x && c_sq.square.y == coords.y) {
					updatePieceCoordinates(
						{x: c_sq.rook_square.x - 1, y: c_sq.rook_square.y - 1},
						{x: c_sq.rook.file - 1, y: c_sq.rook.rank - 1}
					);
					
					break;
				}
			}
		}
		
		sendMoveRequest(piece_info, coords);
		
		console.log('end movePiece()');
	}
	
	function sendMoveRequest(piece_info, coords, new_class)
	{
		turn = false;
		
		$scope.move_count++;		

		var moveData = { piece: { id: piece_info.id, file: piece_info.file, rank: piece_info.rank }, coords: { file: coords.x, rank: coords.y } };
		
		if(typeof new_class != "undefined") {
			moveData.new_class = new_class;
		}
		
		$http({method: 'POST', url:  $scope.game_id + '/move', data: moveData, headers: {'Content-type': 'application/json'}})
			.success( function(data) { 
				awaiting_move_response = false;
				
			}).error(function() {			
				$scope.move_count--;
				
				piece = $scope.board[ coords.y - 1 ][ coords.x - 1 ];
				
				/* reverting move (setting piece on it's former position) */
				$scope.board[ piece_info.rank - 1 ][ piece_info.file - 1 ] = piece;
				$scope.board[ coords.y - 1 ][ coords.x - 1 ] = null;
				
				piece.file = piece_info.file;
				piece.rank = piece_info.rank;
				
				turn = true;
				awaiting_move_response = false;
			});
	}
	
	function isMoveLegal(coords, piece) {
		for(square in piece.moves) {
			if(piece.moves[square].x == coords.x && piece.moves[square].y == coords.y) {
				return true;
			}
		}
	
		return false;
	}
	
	function updatePieceCoordinates(from, to) {
		$scope.board[ to.y ][ to.x ] = $scope.board[ from.y ][ from.x ];
		$scope.board[ from.y ][ from.x ] = null;
		$scope.board[ to.y ][ to.x ].file = to.x + 1;
		$scope.board[ to.y ][ to.x ].rank = to.y + 1;
	}
	
	function updateMovedPiece(last_moved, captured) {
		console.log('updateMovedPiece()');
		
		if(!last_moved) {
			return;
		}
		
		if(captured) {
			console.log('captured:');
			console.log(captured);
			$scope.board[ captured.rank - 1 ][ captured.file - 1 ] = null;
		}
		
		for(var i=0; i<8; i++) {
			for(var j=0; j<8; j++) {
				if( $scope.board[ i ][ j ] && ($scope.board[ i ][ j ].id == last_moved.id )) {
					var piece = $scope.board[ i ][ j ];
					
					console.log('piece:');
					console.log(piece);
					
					updatePieceCoordinates({x: j, y: i}, {x: last_moved.file - 1, y: last_moved.rank - 1});
					
					lastMoveHighlight(last_moved, j+1, i+1);
					
					console.log('after move:');
					console.log($scope.board[ last_moved.rank - 1 ][ last_moved.file - 1 ]);
					
					if(castle && castle.opponent) {
						var cstl = castle.opponent;
						for(var k=0; k<2; k++) {
							var c_sq = cstl[k];
							
							if( c_sq.square.x ==  last_moved.file && c_sq.square.y == last_moved.rank) {
								
								updatePieceCoordinates(
									{x: c_sq.rook_square.x - 1, y: c_sq.rook_square.y - 1},
									{x: c_sq.rook.file - 1, y: c_sq.rook.rank - 1}
								);

								return;
							}
						}
					}
					
					console.log('updateMovedPiece() done');
					return;
				}
			}
		}
	}
	
	function refreshPiecesMoves(pieces) {
		for(piece in pieces) {
			if( $scope.board[ pieces[piece].rank - 1 ][ pieces[piece].file - 1 ] ) {
				$scope.board[ pieces[piece].rank - 1 ][ pieces[piece].file - 1 ].moves = pieces[piece].moves;
			}
		}
	}
	
	function select(piece) {
		selection = piece;
		
		$selection = $chessboard.find("[data-coords='" + piece.file + piece.rank + "']");
		$selection.addClass('square-selected');

		selectionHighlight( piece );	
	}
	
	function clearSelection() {
		clearSelectionHighlight();
		if(selection) {
			$selection = $chessboard.find("[data-coords='" + selection.file + selection.rank + "']");
			$selection.removeClass('square-selected');
			
			selection = null;
		}
	}

	function selectionHighlight(piece) {
		for(square in piece.moves) {
			selection_highlight[square] = $chessboard
				.find("[data-coords='" + piece.moves[square].x + piece.moves[square].y + "']");
			
			if( $scope.board[ piece.moves[square].y -1 ][ piece.moves[square].x -1 ] ) {
				selection_highlight[square].addClass('selection-kill-highlight');
			} else {
				selection_highlight[square].addClass('selection-highlight');
			}
		}
	}
		
	function clearSelectionHighlight() {
		for(square in selection_highlight) {
			selection_highlight[square].removeClass('selection-highlight').removeClass('selection-kill-highlight');
		}
		selection_highlight = [];
	}

	function lastMoveHighlight(piece, new_file, new_rank) {
		clearLastMoveHighlight();
		
		last_move_highlight[0] = $chessboard
				.find("[data-coords='" + piece.file + piece.rank + "']");
		last_move_highlight[1] = $chessboard
				.find("[data-coords='" + new_file + new_rank + "']");
		
		for(i in last_move_highlight) {
			last_move_highlight[i].addClass('last-move-highlight');
		}
	}
	
	function clearLastMoveHighlight() {
		for(i in last_move_highlight) {
			last_move_highlight[i].removeClass('last-move-highlight');
		}
		last_move_highlight = [];
	}
});

polcodeChess.factory('boardFactory', function($http) {
	var board = [];
	
	var factory = [];
	
	factory.getBoardAndPieces = function(game_id) {
		for(var i=0; i<8; i++) {
			board[i] = [];
			for(var j=0; j<8; j++) {
				board[i][j] = null;
			}
		}
		
		$http({method: 'GET', url:  game_id + '/pieces', headers: {'Content-type': 'application/json'}})
			.success(function(data) {
				for(piece in data) {
					var color = data[piece].is_white ? 'White' : 'Black';
					
					board[ data[piece].rank - 1 ][ data[piece].file - 1 ] = {
																				id: parseInt(piece),
																				classname: data[piece].classname,
																				name: data[piece].classname + color,
																				file: data[piece].file,
																				rank: data[piece].rank,
																				is_white: data[piece].is_white,
																				moves: data[piece].moves																			
																				};
				}
			}).error(function() { console.log('Error getting pieces!');	});
	
		return board;
	}
	
	return factory;
});

polcodeChess.filter('reverse', function() {
	return function(items) {
		return items.slice().reverse();
	}
});
