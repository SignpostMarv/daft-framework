<?php
/**
* @author SignpostMarv
*/
declare(strict_types=1);

namespace SignpostMarv\DaftFramework;

use BadMethodCallException;

trait AttachDaftFramework
{
    /**
    * @var Framework|null
    */
    protected $daftFrameworkInstance;

    public function AttachDaftFramework(Framework $framework)
    {
        if ($this->daftFrameworkInstance instanceof Framework) {
            throw new BadMethodCallException(
                'Framework must not be attached if a framework is already attached!'
            );
        }

        $this->daftFrameworkInstance = $framework;
    }

    /**
    * @return Framework|null
    */
    public function DetachDaftFramework()
    {
        $out = $this->daftFrameworkInstance;

        if ($out instanceof Framework) {
            $this->daftFrameworkInstance = null;
        }

        return $out;
    }

    /**
    * @return Framework|null
    */
    public function GetDaftFramework()
    {
        return $this->daftFrameworkInstance;
    }

    public function CheckIfUsingFrameworkInstance(Framework ...$instances) : bool
    {
        return in_array($this->daftFrameworkInstance, $instances, true);
    }
}
