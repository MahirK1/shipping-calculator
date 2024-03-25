
Custom Shipping Calculator Plugin

This plugin, named "Custom Shipping Calculator," is designed to enhance the functionality of WooCommerce by providing dynamic shipping cost calculations based on the contents of the user's cart and their shipping address. Below is a breakdown of its key features and functionalities:

Geocoding Address:
Utilizes the Google Maps API to geocode user-provided shipping addresses. This is crucial for determining the distance between the user's location and specified warehouse locations.
Distance Calculation:

Implements the Haversine formula to accurately calculate the distance between two geographical coordinates (latitude and longitude). This distance calculation is fundamental for determining shipping costs based on the user's location relative to predefined warehouse locations.
Dynamic Shipping Cost Calculation:

Dynamically calculates shipping costs based on the user's shipping address and the distance to predefined warehouse locations.
Shipping costs are determined by predefined pricing tiers for different zones, considering maximum delivery distances and additional charges per mile for zones beyond the predefined limits.
Zone-based Pricing:

Defines distinct pricing structures for different zones (e.g., red, blue, yellow, etc.), each with its own maximum delivery distance and pricing for various transport types (e.g., small truck, large dump truck, flatbed).
Product Category-based Pricing:

Customizes shipping costs based on the categories of products in the user's cart.
Shipping costs are adjusted depending on the weight and type of products, such as soils, sands, boulders, compost, or mulch.
Integration with WooCommerce:

Hooks into WooCommerce's cart calculation process to seamlessly add shipping fees based on the calculated costs.
Ensures compatibility with WooCommerce's existing functionality and checkout process.
