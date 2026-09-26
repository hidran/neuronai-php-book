<?php

declare(strict_types=1);

namespace NeuronBook\Ch21;

use RuntimeException;

/**
 * Section 21.6 - thrown INTO the stream when the browser has gone.
 *
 * Throwing into the generator lets the workflow settle the run as failed,
 * which releases the thread for the next message. Merely breaking out of the
 * loop leaves the run marked "running" under its lease.
 */
class ClientDisconnected extends RuntimeException
{
}
