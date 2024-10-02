<?php

namespace App\Utilities\Handlers;

use SplStack;
use SplQueue;
use SplMinHeap;
use SplMaxHeap;
use SplPriorityQueue;
use SplDoublyLinkedList;
use SplFixedArray;
use SplObjectStorage;

/**
 * Class DataStructureHandler
 *
 * Provides utility methods for working with various SPL (Standard PHP Library) data structures.
 */
class DataStructureHandler
{
	// Stack Methods

	/**
	 * Create a new stack (LIFO) using SplStack.
	 *
	 * @return SplStack The created stack.
	 */
	public function createStack(): SplStack
	{
		return new SplStack();
	}

	/**
	 * Push an item onto the stack.
	 *
	 * @param SplStack $stack The stack to push onto.
	 * @param mixed $item The item to push.
	 * @return void
	 */
	public function pushToStack(SplStack $stack, $item): void
	{
		$stack->push($item);
	}

	/**
	 * Pop an item from the stack.
	 *
	 * @param SplStack $stack The stack to pop from.
	 * @return mixed The popped item.
	 */
	public function popFromStack(SplStack $stack)
	{
		return $stack->pop();
	}

	// Queue Methods

	/**
	 * Create a new queue (FIFO) using SplQueue.
	 *
	 * @return SplQueue The created queue.
	 */
	public function createQueue(): SplQueue
	{
		return new SplQueue();
	}

	/**
	 * Enqueue an item into the queue.
	 *
	 * @param SplQueue $queue The queue to enqueue to.
	 * @param mixed $item The item to enqueue.
	 * @return void
	 */
	public function enqueue(SplQueue $queue, $item): void
	{
		$queue->enqueue($item);
	}

	/**
	 * Dequeue an item from the queue.
	 *
	 * @param SplQueue $queue The queue to dequeue from.
	 * @return mixed The dequeued item.
	 */
	public function dequeue(SplQueue $queue)
	{
		return $queue->dequeue();
	}

	// Heap Methods

	/**
	 * Create a minimum heap (MinHeap) using SplMinHeap.
	 *
	 * @return SplMinHeap The created MinHeap.
	 */
	public function createMinHeap(): SplMinHeap
	{
		return new SplMinHeap();
	}

	/**
	 * Create a maximum heap (MaxHeap) using SplMaxHeap.
	 *
	 * @return SplMaxHeap The created MaxHeap.
	 */
	public function createMaxHeap(): SplMaxHeap
	{
		return new SplMaxHeap();
	}

	// Priority Queue Methods

	/**
	 * Create a priority queue using SplPriorityQueue.
	 *
	 * @return SplPriorityQueue The created priority queue.
	 */
	public function createPriorityQueue(): SplPriorityQueue
	{
		return new SplPriorityQueue();
	}

	// Doubly Linked List Methods

	/**
	 * Create a doubly linked list using SplDoublyLinkedList.
	 *
	 * @return SplDoublyLinkedList The created doubly linked list.
	 */
	public function createDoublyLinkedList(): SplDoublyLinkedList
	{
		return new SplDoublyLinkedList();
	}

	// Fixed Array Methods

	/**
	 * Create a fixed-size array using SplFixedArray.
	 *
	 * @param int $size The size of the fixed array.
	 * @return SplFixedArray The created fixed array.
	 */
	public function createFixedArray(int $size): SplFixedArray
	{
		return new SplFixedArray($size);
	}

	// Object Storage Methods

	/**
	 * Create an object storage using SplObjectStorage.
	 *
	 * @return SplObjectStorage The created object storage.
	 */
	public function createObjectStorage(): SplObjectStorage
	{
		return new SplObjectStorage();
	}

	/**
	 * Attach an object to object storage.
	 *
	 * @param SplObjectStorage $storage The object storage.
	 * @param object $object The object to attach.
	 * @param mixed $info Optional information to store with the object.
	 * @return void
	 */
	public function attachToObjectStorage(SplObjectStorage $storage, $object, $info = null): void
	{
		$storage->attach($object, $info);
	}

	/**
	 * Detach an object from object storage.
	 *
	 * @param SplObjectStorage $storage The object storage.
	 * @param object $object The object to detach.
	 * @return void
	 */
	public function detachFromObjectStorage(SplObjectStorage $storage, $object): void
	{
		$storage->detach($object);
	}

	// Count Method

	/**
	 * Count the number of elements in the given data structure.
	 *
	 * @param \SplQueue|\SplStack|array $dataStructure The data structure to count.
	 * @return int The number of elements.
	 */
	public function count($dataStructure): int
	{
		if (is_array($dataStructure)) {
			return count($dataStructure);
		}

		if ($dataStructure instanceof \Countable) {
			return $dataStructure->count();
		}

		throw new \InvalidArgumentException("Unsupported data structure for counting.");
	}
}
