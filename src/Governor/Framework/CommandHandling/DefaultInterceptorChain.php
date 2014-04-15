<?php

/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * The software is based on the Axon Framework project which is
 * licensed under the Apache 2.0 license. For more information on the Axon Framework
 * see <http://www.axonframework.org/>.
 * 
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.governor-framework.org/>.
 */

namespace Governor\Framework\CommandHandling;

use Governor\Framework\UnitOfWork\UnitOfWorkInterface;

/**
 * Mechanism that takes care of interceptor and event handler execution.
 */
class DefaultInterceptorChain implements InterceptorChainInterface
{

    private $command;
    private $handler;
    private $chain; //Iterator<? extends CommandHandlerInterceptor> chain;
    private $unitOfWork;

    /**
     * Initialize the default interceptor chain to dispatch the given <code>command</code>, through the
     * <code>chain</code>, to the <code>handler</code>.
     *
     * @param command    The command to dispatch through the interceptor chain
     * @param unitOfWork The UnitOfWork the command is executed in
     * @param handler    The handler for the command
     * @param chain      The interceptor composing the chain
     */
    public function __construct(CommandMessageInterface $command,
            UnitOfWorkInterface $unitOfWork, CommandHandlerInterface $handler,
            array $chain)
    {
        $this->command = $command;
        $this->handler = $handler;
        $this->chain = new \ArrayIterator($chain);
        $this->unitOfWork = $unitOfWork;
    }

    /**
     * {@inheritDoc}
     */
    public function proceed(CommandMessageInterface $commandProceedWith = null)
    {
        if (null !== $commandProceedWith) {
            $this->command = $commandProceedWith;
        }

        if ($this->chain->valid()) {
            $next = $this->chain->current();
            $this->chain->next();

            return $next->handle($this->command, $this->unitOfWork, $this);
        } else {
            return $this->handler->handle($this->command, $this->unitOfWork);
        }
        // if (chain.hasNext()) {
        //   return chain.next().handle(commandProceedWith, unitOfWork, this);
        //} else {
        //  return handler.handle(commandProceedWith, unitOfWork);
        //}
    }
   
}