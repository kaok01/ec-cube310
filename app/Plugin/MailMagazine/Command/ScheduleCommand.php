<?php
/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) 2000-2015 LOCKON CO.,LTD. All Rights Reserved.
 *
 * http://www.lockon.co.jp/
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */


namespace Plugin\MailMagazine\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

class ScheduleCommand extends \Knp\Command\Command
{

    protected $app;

    public function __construct(\Eccube\Application $app, $name = null) 
    {
        parent::__construct($name);
        $this->app = $app;
    }

    protected function configure() 
    {
        $this
            ->setName('schedule:execute')
            ->addArgument('mode', InputArgument::REQUIRED, 'mode(scheduled)', null)
            ->addOption('code', null, InputOption::VALUE_OPTIONAL, 'plugin code')
            ->setDescription('schedule commandline execute batch.')
            ->setHelp(<<<EOF
The <info>%command.name%</info> execute schedule runner;
EOF
            );
    }


    protected function getPluginFromCode($pluginCode) 
    {
        return $this->app['eccube.repository.plugin']->findOneBy(array('del_flg'=>0, 'code'=>$pluginCode));
    }

    protected function execute(InputInterface $input, OutputInterface $output) 
    {
        $this->app->initialize();
        $this->app->boot();

        $mode = $input->getArgument('mode');
        $code = $input->getOption('code');
        //$runForce = $input->getOption('run-force');

        $service = $this->app['eccube.service.plugin'];

        if ($mode == 'scheduled') {
            // 設置済ファイルからインストール
            if ($code) {
                $plugin = $this->getPluginFromCode($code);
                if ($plugin) {
                    //consoleでinitializePluginを実行済を前提
                    if($this->app['eccube.plugin.mail_magazine.service.mail']){
                        $this->app['eccube.plugin.mail_magazine.service.mail']->ScheduleExec($output);
                        $output->writeln('success1');
                        return;
                    }
                    $output->writeln('undefined service.');
                    return;

                }

                $output->writeln('code is undefined.');

                return;
            }

            $output->writeln('code is required.');

            return;
        }

        $output->writeln('undefined mode.');
    }
}
