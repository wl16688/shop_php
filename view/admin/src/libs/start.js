#!/usr/bin/env node

const chalk = require("chalk");

// 打印欢迎信息
console.log(
  chalk
    .hex("#DEADED")
    .underline("😄 Hello ~ 欢迎使用CRMEB Pro版，我们将竭诚为您服务！")
);
console.log(
  chalk.yellow("[提示] 点击这里可以我们的更多产品~ ") +
    chalk.blue.underline("https://www.crmeb.com")
);
console.log(
  chalk.yellow("[提示] 点击这里可以查看开发文档喔~ ") +
    chalk.blue.underline("https://doc.crmeb.com")
);
console.log(
  chalk.yellow("[提示] 点击这里可以进入我们的论坛社区~ ") +
    chalk.blue.underline("https://www.crmeb.com/ask")
);
console.log(chalk.blue("info - [你知道吗？] 按 Ctrl+C 可以停止服务呢~"));
