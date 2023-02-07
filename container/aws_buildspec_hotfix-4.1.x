version: 0.2

env:
  shell: bash

phases:
  pre_build:
    commands:
      - echo Logging in to Amazon ECR...
      - aws ecr-public get-login-password --region us-east-1 | docker login --username AWS --password-stdin public.ecr.aws/u0z2s2z8
      - echo Logging in to Docker Hub
      - docker login --username skoranda --password $HUB_DOCKER_COM_TOKEN
  build:
    commands:
      - echo Build started on `date`
      - SHORT_COMMIT_HASH="${CODEBUILD_RESOLVED_SOURCE_VERSION:0:8}"
      - container/build.sh --image_registry=public.ecr.aws --repository=u0z2s2z8 --label=hotfix-4.1.x-$SHORT_COMMIT_HASH --suffix=$CODEBUILD_BUILD_NUMBER registry all
      - container/build.sh --image_registry=public.ecr.aws --repository=u0z2s2z8 --label=hotfix-4.1.x-$SHORT_COMMIT_HASH --suffix=$CODEBUILD_BUILD_NUMBER crond
  post_build:
    commands:
      - echo Build completed on `date`
      - echo Pushing the Docker images to AWS public repository...
      - docker push public.ecr.aws/u0z2s2z8/comanage-registry:hotfix-4.1.x-$SHORT_COMMIT_HASH-basic-auth-$CODEBUILD_BUILD_NUMBER
      - docker push public.ecr.aws/u0z2s2z8/comanage-registry:hotfix-4.1.x-$SHORT_COMMIT_HASH-mod_auth_openidc-$CODEBUILD_BUILD_NUMBER
      - docker push public.ecr.aws/u0z2s2z8/comanage-registry:hotfix-4.1.x-$SHORT_COMMIT_HASH-shibboleth-sp-supervisor-$CODEBUILD_BUILD_NUMBER
      - docker push public.ecr.aws/u0z2s2z8/comanage-registry-cron:hotfix-4.1.x-$SHORT_COMMIT_HASH-$CODEBUILD_BUILD_NUMBER
