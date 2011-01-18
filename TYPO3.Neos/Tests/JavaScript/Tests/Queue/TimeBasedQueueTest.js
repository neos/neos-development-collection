Ext.ns('F3.TYPO3.Queue');

describe('Time based queue', function() {

	var queue = F3.TYPO3.Queue.TimeBasedQueue;
	queue.initialize();

	describe('Basic events', function() {
		beforeEach(function() {
			queue.reset();
		});

		it ('Register methods', function() {
			queue.add(function() {}, 20);
			queue.add(function() {}, 0);
			expect(queue.count()).toEqual(2);
		});

		it ('Reset queue', function() {
			queue.add(function() {}, 20);
			queue.add(function() {}, 0);
			queue.reset();
			expect(queue.count()).toEqual(0);
		});

		it ('Test if running status is stopped after execution of 1 action, independed of te sleeptime', function() {
			queue.add(function() {}, 20);
			queue.start();
			expect(queue.isRunning()).toEqual(false);
		});

		it ('Test if running status is true after starting the queue', function() {
			queue.add(function() {}, 30);
			queue.add(function() {}, 30);
			queue.start();
			expect(queue.isRunning()).toEqual(true);
		});

		it ('Test if running status is stopped after stopping the queue', function() {
			expect(queue.isRunning()).toEqual(false);
			queue.add(function() {}, 20);
			queue.add(function() {}, 20);
			queue.start();
			expect(queue.isRunning()).toEqual(true);
			queue.stop();
			expect(queue.isRunning()).toEqual(false);
		});
	});

	describe ('Test execution of the queue with all delayed executions', function() {
		it ('Init queue', function() {
			queue.reset();
			expect(queue.count()).toEqual(0);
			queue.add(function() {}, 10);
			queue.add(function() {}, 10);
			queue.add(function() {}, 10);
			expect(queue.count()).toEqual(3);
			queue.start();
		});

		it ('Test if the first item is removed from the queue', function() {
			expect(queue.count()).toEqual(2);
			waits(10);
		});

		it ('Test if the second item is removed from the queue', function() {
			expect(queue.count()).toEqual(1);
			waits(10);
		});

		it ('Test if queue is empty and stopped', function() {
			expect(queue.count()).toEqual(0);
			expect(queue.isRunning()).toEqual(false);
		});
	});

	describe ('Test execution of the queue with some none-delayed executions', function() {
		it ('Init queue', function() {
			queue.reset();
			expect(queue.count()).toEqual(0);
			queue.add(function() {}, 50);
			queue.add(function() {}, 50);
			queue.add(function() {}, 0);
			queue.add(function() {}, 0);
			queue.add(function() {}, 0);
			queue.add(function() {}, 0);
			queue.add(function() {}, 50);
			queue.add(function() {}, 50);
			expect(queue.count()).toEqual(8);
			queue.start();
		});

		it ('Test if the first item is removed from the queue', function() {
			expect(queue.count()).toEqual(7);
			waits(50);
		});

		it ('Test if the second item is removed from the queue', function() {
			expect(queue.count()).toEqual(6);
			waits(50);
		});

		it ('Test if the non-delayed actions are removed from the queue', function() {
			expect(queue.count()).toEqual(1);
			waits(25);
		});

		it ('Test if last action is still running', function() {
			expect(queue.count()).toEqual(1);
			expect(queue.isRunning()).toEqual(true);
			waits(50);
		});

		it ('Test if queue is empty and stopped', function() {
			expect(queue.count()).toEqual(0);
			expect(queue.isRunning()).toEqual(false);
		});
	});

});