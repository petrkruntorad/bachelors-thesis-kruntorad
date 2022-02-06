import os, time, requests, logging
from crontab import CronTab
logging.basicConfig(filename='log.txt', encoding='utf-8', level=logging.ERROR)

# variables
sensors = []
temperature = None
rowComment = 'writeTemperature'
parentDirectory = os.path.dirname(os.path.realpath(__file__))
mainScriptPath = parentDirectory + "/main.py"
cron = CronTab(user='root')

def checkIfCronJobExist(comment: str):
    exist = False
    try:
        for job in cron:
            if job.comment == comment:
                exist = True
        return exist
    except RuntimeError as exception:
        logging.error("Internal error: " + str(exception))

def setCronJob(interval: str):
    try:
        if checkIfCronJobExist(rowComment):
            updateCronJob(interval)
        else:
            job = cron.new(command='python ' + mainScriptPath, comment=rowComment)
            job.setall(interval)
            cron.write()
    except RuntimeError as exception:
        logging.error("Internal error: " + str(exception))

def updateCronJob(interval: str):
    try:
        for job in cron:
            if job.comment == rowComment:
                job.setall(interval)
            cron.write()

    except RuntimeError as exception:
        logging.error("Internal error: " + str(exception))

def getMainNetworkInterfaceMacAdress():
    try:
        cmdipa = os.popen('ip a | grep link/ether').read()
        cmdipa = cmdipa.splitlines()
        allMacAddresses = []
        for mac in cmdipa:
            mac = mac.split(' ')
            allMacAddresses.append(mac[5])

        return allMacAddresses[0]
    except RuntimeError as exception:
        logging.error("Internal error: " + str(exception))

def touchServer(uniqueHash: str, touchUrl: str):
    try:
        macAddress = getMainNetworkInterfaceMacAdress()

        session = requests.Session()

        data = {'uniqueHash': uniqueHash, 'macAddress': macAddress}

        request = session.post(url=touchUrl, data=data)

        return request.text
    except RuntimeError as exception:
        logging.error("Internal error: " + str(exception))

def updateConfig(uniqueHash: str, updateUrl: str, configData):
    postData = {'uniqueHash':uniqueHash}
    response = requests.post(updateUrl, data=postData)
    if response.status_code == 200:
        data = response.content
        pathToConfig = parentDirectory + '/config.json'
        with open(pathToConfig, 'wb') as s:
            s.write(data)
        if 'writeInterval' in configData.keys():
            updateCronJob(configData['writeInterval'])
        else:
            logging.error("Write interval is missing in config file.")
    else:
        print(response.content)
        print(response.status_code)

def loadSensors():
    try:
        cmdLs = os.popen('ls /sys/bus/w1/devices/ | grep ^28').read()
        cmdLs = cmdLs.splitlines()
        for id in cmdLs:
            sensors.append(id)
        return sensors
    except RuntimeError as exception:
        logging.error("Internal error: " + str(exception))

def saveTemperatures(sensors: list, writeUrl: str, uniqueHash: str):
    try:
        for singleSensor in sensors:
            with open("/sys/bus/w1/devices/" + singleSensor + "/w1_slave", "r") as file:
                for line in file:
                    if "t=" in line:
                        lineSplit = line.split(" ")
                        for value in lineSplit:
                            if value.startswith("t="):
                                temperature = float(value[2:]) / 1000
                                print(temperature)

            if temperature:
                session = requests.Session()

                data = {'sensorId': singleSensor, 'rawSensorData': temperature, 'uniqueHash': uniqueHash}

                insert_request = session.post(url=writeUrl, data=data)

                print(insert_request.text)
            else:
                raise ValueError("Temperature is missing.")
    except RuntimeError as exception:
        logging.error("Internal error: " + str(exception))

