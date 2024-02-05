#!/usr/bin/env python3

import configparser
import json
from collections import defaultdict
from mysql.connector import (connection)
import urllib.parse



def getRestStats(cursor, users, sessions):
    restapi_query = """
SELECT *
FROM
    mod_logger.log_table
WHERE
    ident = 'rest.logger.db'
    AND message like '%raw-data%'
    AND logtime BETWEEN %s AND %s
"""
    queries = 0

    result = cursor.execute(restapi_query, ('2023-10-01', '2024-01-01'))
    rows = cursor.fetchall()
    for row in rows:
        message = json.loads(row['message'])
        query = urllib.parse.parse_qs(message['query'])
        
        if 'Bearer' not in query:
            print(query)
            continue

        bearer = query['Bearer'][0]
        user = bearer.split(".")[0]

        session = message['data']['token']

        queries += 1
        sessions.add(session)
        users[user] += 1

    return queries

def getControllerStats(cursor, users, sessions):
    controller_query = """
SELECT *
FROM
    mod_logger.log_table
WHERE
    ident = 'controller.log'
    AND message like '%Bearer%'
    AND logtime BETWEEN %s AND %s
"""

    users = defaultdict(int)
    sessions = set()
    queries = 0

    result = cursor.execute(controller_query, ('2023-10-01', '2024-01-01'))
    rows = cursor.fetchall()
    for row in rows:
        message = json.loads(row['message'])
        message = json.loads(message['message'])

        bearer = message['post']['Bearer']
        user = bearer.split(".")[0]
        session = message['data']['token']

        queries += 1
        sessions.add(session)
        users[user] += 1

    return queries


def main():
    xdmod_config = configparser.ConfigParser()
    xdmod_config.read('./portal_settings.ini')
    dwc = xdmod_config['datawarehouse']

    cnx = connection.MySQLConnection(user=dwc['user'].strip("'"), password=dwc['pass'].strip("'"),
    host=dwc['host'].strip("'"), database=dwc['database'].strip("'"))

    users = defaultdict(int)
    sessions = set()

    if cnx and cnx.is_connected():
        cursor = cnx.cursor(dictionary=True)
        ccount = getControllerStats(cursor, users, sessions)
        rcount = getRestStats(cursor, users, sessions)
        cnx.close()
    else:
        print("Could not connect")

    print("Controller Calls {}, Rest Calls {}. Total Calls: {}, Total Distinct Users {}, Total Sessions {}".format(ccount, rcount, ccount + rcount, len(users), len(sessions)))

if __name__ == "__main__":
    main()
