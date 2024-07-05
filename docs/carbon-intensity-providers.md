# Carbon intensity data provides

Carbon intensity is the measure of the CO2 emission of electricity production. It merely depends:
- on the electricity mix of the country (gaz, coal, nuclear, wind, solar, hydro...)
- on time, with important variations from day to day.

Currently, the GLPI Carbon plugin uses the following data providers:
- CO2 Signal
- electricymaps
- ODRÉ (opendata réseaux-énergies)

## CO2 Signal

This data provider is a free and reduced version of _electricity maps_. It provides real-time data on electricity mix for numerous countries. No historical data is available.

The API requires a free API key and has a rate limit. It is free for non-commercial use.

Provider: https://www.co2signal.com/

API documentation: https://docs.co2signal.com/#introduction

Base URL for API: https://api.co2signal.com/v1/

## Electricity maps

This data provider provides real-time and historical data on electricity mix for numerous countries (mainly Europe and North America).

The API requires a key that allows free use during 1 month. After expiration of the free trial, a product is available starting at 500€ per month. A rate limit is set, with different limits for the free version and the commercial product.

Provider: https://www.electricitymaps.com/

API documentation: https://static.electricitymaps.com/api/docs/index.html

Base URL for API: https://api.electricitymap.org

## ODRÉ

This data provider, namely _Données éCO2mix nationales temps réel_, provides real-time data on electricity mix for France only.

Data are available from month M-1 to recent hours, the delay in hours is not specified. Timestep is 1/4 hour. Data are refreshed every hour.

This API does not require authentication.

Provider: https://www.rte-france.com/eco2mix

API documentation: https://odre.opendatasoft.com/explore/dataset/eco2mix-national-tr/api/?disjunctive.nature

API console: https://odre.opendatasoft.com/api/explore/v2.1/console

Full documentation on ODSQL (Opendatasoft Query Language): https://help.opendatasoft.com/apis/ods-explore-v2/explore_v2.1.html

Base URL for API: https://odre.opendatasoft.com/api/explore/v2.1/catalog/datasets/eco2mix-national-tr/records

## ENTSOE

European Network of Transmission System Operators for Electricity: https://www.entsoe.eu/

Dashboard: https://transparency.entsoe.eu/dashboard/show
