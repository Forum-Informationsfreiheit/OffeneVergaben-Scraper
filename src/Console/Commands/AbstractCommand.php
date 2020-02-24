<?php

namespace OffeneVergaben\Console\Commands;

use Carbon\Carbon;
use Illuminate\Support\Str;
use \Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Convenience Wrapper for Commands.
 *
 * Adds streamlined interface for message and error handling.
 *
 * Class AbstractCommand
 * @package OffeneVergaben\Console\Commands
 */
abstract class AbstractCommand extends Command
{
    /** @var \OffeneVergaben\Console\Application $app */
    protected $app;

    /** @var \Symfony\Component\Console\Input\InputInterface $input */
    protected $input;

    /** @var \Symfony\Component\Console\Output\OutputInterface $output */
    protected $output;

    /**
     *
     */
    public function __construct() {
        parent::__construct();

        $this->app = app();
    }

    /**
     * Called before exe
     */
    protected function init() {
        // handle database connect and other code
        // that needs to be run before command execution
    }

    /**
     * Main entry point for command execution.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws \Exception
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        $this->input = $input;
        $this->output = $output;

        $start = Carbon::now();

        try {
            $this->info($this->getName() . ' started');
            $this->output->writeln('');

            $this->init();
            $this->exe();

            $this->output->writeln('');
            $runtime = $start->diffInSeconds(Carbon::now());
            $this->info($this->getName() . ' finished in '.$runtime.' seconds');
        } catch (\Exception $ex) {
            // implement a default way of logging exceptions
            $this->app->getLogger()->addError($this->getName() . ' Exception occurred.', [
                'file' => $ex->getFile(),
                'line' => $ex->getLine(),
                'code' => $ex->getCode(),
                'message' => $ex->getMessage(),
                'trace' => $ex->getTraceAsString()      // todo is there a way to print a nice formatted trace to the log?
            ]);

            // now rethrow to trigger symfonys way of dealing with exceptions during command execution
            throw $ex;
        }

        return 0;
    }

    /**
     * Inheriting classes must implement this
     *
     * @return
     */
    protected abstract function exe();

    /**
     * Write a message to the console and to the log file.
     *
     * @param string $message
     */
    protected function info($message) {
        // write original message to output interface
        $this->output->writeln($message);

        // write info to logger
        $prefix = $this->getName();
        $prefixedMessage = !Str::startsWith($message,$prefix) ? $prefix . ' ' . $message : $message;

        $this->app->getLogger()->addInfo(strip_tags($prefixedMessage));
    }

    /**
     * Write a error message to the console and to the log file.
     *
     * @param string $message
     */
    protected function error($message) {
        // write original message to output interface
        $this->output->writeln('<error>' . $message . '</error>');

        // write info to logger
        $prefix = $this->getName();
        $prefixedMessage = !Str::startsWith($message,$prefix) ? $prefix . ' ' . $message : $message;

        $this->app->getLogger()->addError(strip_tags($prefixedMessage));
    }

    /**
     * Simple implementation for (interruption) signal handling.
     *
     * This method will not be called unless inheriting commands purposely invoke it.
     *
     * IMPORTANT: When listening to signals commands must include
     *            $this->checkForInterrupt() calls during program flow.
     *            Otherwise signals never get dispatched and
     *            the handler will never be invoked.
     *
     * Listens to SIGINT and SIGQUIT by default.
     * Override as needed.
     */
    protected function selfHandleInterrupts() {
        // handle CTRL+C and CTRL+D interruption signals
        pcntl_signal(SIGINT,  [ $this, 'handleInterrupts' ]);
        pcntl_signal(SIGQUIT, [ $this, 'handleInterrupts' ]);
    }

    /**
     * By controlling when to listen to signal input,
     * we can make sure no critical program part gets interrupted and
     * delay the execution of the signal until we can perform a controlled exit.
     *
     * For inheriting commands: at appropriate points in the program flow
     * (e.g. at the beginning or end of a processing loop) call this method.
     */
    protected function checkForInterrupts() {
        pcntl_signal_dispatch();
    }

    /**
     * Default implementation for handling an interrupt.
     * Override as needed.
     *
     * @param int $sigNo
     */
    public function handleInterrupts($sigNo) {
        $this->info('<comment>Command stopped by user.</comment>');
        exit;
    }
}