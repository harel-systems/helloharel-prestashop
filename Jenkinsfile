#!/usr/bin/env groovy

// HelloHarel Prestashop module build pipeline

pipeline {
    agent any
    options {
        buildDiscarder(logRotator(numToKeepStr: '50', artifactNumToKeepStr: '20', daysToKeepStr: '30', artifactDaysToKeepStr:'30'))
    }
    
    parameters {
        string(
            name: 'PHID',
            defaultValue: '',
            description: 'Phabricator PHID'
        )
    }
    
    stages {
        stage('Clean Workspace') {
            steps {
                // keep "old" php and node components
                sh "git clean -fdx"
            }
        }
        
        stage('Checkout') {
            steps {
                checkout scm
            }
        }
        
        stage('Composer') {
            steps {
                sh "composer install"
                sh "composer dump-autoload"
            }
        }
        
        stage('PHP Linting') {
            steps {
                sh "find . -name '*.php' -exec php -l {} +"
            }
        }
        
        stage('YAML Linting') {
            steps {
                sh "find . -name '*.yml' -exec yamllint {} +"
            }
        }
        
        stage('JSON Linting') {
            steps {
                sh "find . -name '*.json' -exec jsonlint-php {} +"
            }
        }
        
        stage('Deploy') {
            steps {
                sh """
#cat <<EOF | sftp 2132118@sftp.sd3.gpaas.net
#put -r ./* vhosts/prestashop.harelsystems.io/htdocs/modules/helloharel
#EOF
lftp -c \"open -u 2132118, sftp://sftp.sd3.gpaas.net && mirror -R -e -x 'Jenkinsfile|\\..*' ./ vhosts/prestashop.harelsystems.io/htdocs/modules/helloharel/\"
                """
            }
        }
    }
    
    post {
        success {
            sh "test -z \"${PHID}\" || echo '{\"buildTargetPHID\":\"$PHID\",\"type\":\"pass\"}' | arc call-conduit --conduit-uri https://phabricator.harelsystems.io/api/ harbormaster.sendmessage"
        }
        failure {
            sh "test -z \"${PHID}\" || echo '{\"buildTargetPHID\":\"$PHID\",\"type\":\"fail\"}' | arc call-conduit --conduit-uri https://phabricator.harelsystems.io/api/ harbormaster.sendmessage"
        }
        aborted {
            sh "test -z \"${PHID}\" || echo '{\"buildTargetPHID\":\"$PHID\",\"type\":\"pass\"}' | arc call-conduit --conduit-uri https://phabricator.harelsystems.io/api/ harbormaster.sendmessage"
        }
    }
}
