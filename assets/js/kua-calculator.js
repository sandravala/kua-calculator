/**
 * Kua Calculator Frontend JavaScript
 * Handles all the calculator functionality and AJAX product loading
 */
jQuery(document).ready(function($) {
    /**
     * Solar new year dates (Li Chun) for years 1920-2080
     * These dates represent the beginning of spring in the Chinese solar calendar
     */
    const solarNewYearDates = {
        1920: "02-04", 1921: "02-04", 1922: "02-04", 1923: "02-04", 1924: "02-05",
        1925: "02-04", 1926: "02-04", 1927: "02-04", 1928: "02-05", 1929: "02-04",
        1930: "02-04", 1931: "02-04", 1932: "02-05", 1933: "02-04", 1934: "02-04",
        1935: "02-04", 1936: "02-05", 1937: "02-04", 1938: "02-04", 1939: "02-04",
        1940: "02-05", 1941: "02-04", 1942: "02-04", 1943: "02-04", 1944: "02-05",
        1945: "02-04", 1946: "02-04", 1947: "02-04", 1948: "02-05", 1949: "02-04",
        1950: "02-04", 1951: "02-04", 1952: "02-05", 1953: "02-04", 1954: "02-04",
        1955: "02-04", 1956: "02-05", 1957: "02-04", 1958: "02-04", 1959: "02-04",
        1960: "02-05", 1961: "02-04", 1962: "02-04", 1963: "02-04", 1964: "02-05",
        1965: "02-04", 1966: "02-04", 1967: "02-04", 1968: "02-05", 1969: "02-04",
        1970: "02-04", 1971: "02-04", 1972: "02-05", 1973: "02-04", 1974: "02-04",
        1975: "02-04", 1976: "02-05", 1977: "02-04", 1978: "02-04", 1979: "02-04",
        1980: "02-05", 1981: "02-04", 1982: "02-04", 1983: "02-04", 1984: "02-05",
        1985: "02-04", 1986: "02-04", 1987: "02-04", 1988: "02-04", 1989: "02-04",
        1990: "02-04", 1991: "02-04", 1992: "02-04", 1993: "02-04", 1994: "02-04",
        1995: "02-04", 1996: "02-04", 1997: "02-04", 1998: "02-04", 1999: "02-04",
        2000: "02-04", 2001: "02-04", 2002: "02-04", 2003: "02-04", 2004: "02-04",
        2005: "02-04", 2006: "02-04", 2007: "02-04", 2008: "02-04", 2009: "02-04",
        2010: "02-04", 2011: "02-04", 2012: "02-04", 2013: "02-04", 2014: "02-04",
        2015: "02-04", 2016: "02-04", 2017: "02-03", 2018: "02-04", 2019: "02-04",
        2020: "02-04", 2021: "02-03", 2022: "02-04", 2023: "02-04", 2024: "02-04",
        2025: "02-03", 2026: "02-04", 2027: "02-04", 2028: "02-04", 2029: "02-03",
        2030: "02-04", 2031: "02-04", 2032: "02-04", 2033: "02-03", 2034: "02-04",
        2035: "02-04", 2036: "02-04", 2037: "02-03", 2038: "02-04", 2039: "02-04",
        2040: "02-04", 2041: "02-03", 2042: "02-04", 2043: "02-04", 2044: "02-04",
        2045: "02-03", 2046: "02-04", 2047: "02-04", 2048: "02-04", 2049: "02-03",
        2050: "02-04", 2051: "02-04", 2052: "02-04", 2053: "02-03", 2054: "02-04",
        2055: "02-04", 2056: "02-04", 2057: "02-03", 2058: "02-04", 2059: "02-04",
        2060: "02-04", 2061: "02-03", 2062: "02-04", 2063: "02-04", 2064: "02-04",
        2065: "02-03", 2066: "02-04", 2067: "02-04", 2068: "02-04", 2069: "02-03",
        2070: "02-04", 2071: "02-04", 2072: "02-04", 2073: "02-03", 2074: "02-04",
        2075: "02-04", 2076: "02-04", 2077: "02-03", 2078: "02-04", 2079: "02-04",
        2080: "02-04"
    };
    
    /**
     * Calculate Kua number based on birth date and gender
     * 
     * @param {Date} birthDate - Birth date as Date object
     * @param {string} gender - 'male' or 'female'
     * @return {number|string} - Kua number or 'error'
     */
    function calculateKua(birthDate, gender) {
        const year = birthDate.getFullYear();
        const month = birthDate.getMonth() + 1; // JavaScript months are 0-11
        const day = birthDate.getDate();
        
        // Check if year is supported
        if (!solarNewYearDates[year]) {
            return 'error';
        }
        
        // Check if person was born before solar new year
        let adjustedYear = year;
        let solarDate = solarNewYearDates[year];
        let [solarMonth, solarDay] = solarDate.split("-").map(Number);
        
        if (month < solarMonth || (month === solarMonth && day < solarDay)) {
            adjustedYear -= 1;
        }
        
        // Sum digits of the adjusted birth year
        let sum = 0;
        adjustedYear.toString().split('').forEach(function(digit) {
            sum += parseInt(digit);
        });
        
        // If sum is double-digit, sum again
        while (sum >= 10) {
            let newSum = 0;
            sum.toString().split('').forEach(function(digit) {
                newSum += parseInt(digit);
            });
            sum = newSum;
        }
        
        // Calculate Kua number based on gender
        let kuaNumber;
        if (gender === 'male') {
            kuaNumber = 11 - sum;
        } else {
            kuaNumber = sum + 4;
        }
        
        // If result is double-digit, sum digits again
        while (kuaNumber >= 10) {
            let newKua = 0;
            kuaNumber.toString().split('').forEach(function(digit) {
                newKua += parseInt(digit);
            });
            kuaNumber = newKua;
        }
        
        // Special cases: if Kua = 5 for males, become 2; if Kua = 5 for females, become 8
        if (kuaNumber === 5) {
            kuaNumber = (gender === 'male') ? 2 : 8;
        }
        
        return kuaNumber;
    }
    
    /**
     * Load associated products for a Kua number via AJAX
     * 
     * @param {number} kuaNumber - The Kua number to load products for
     */
    function loadKuaProducts(kuaNumber) {
        // Show loading indicator in products list
        $('#kua-product-recommendations').hide();
        $('#kua-products-list').html('<p class="kua-loading">' + kua_calculator_vars.loading_text + '</p>');
        
        // AJAX request to get products
        $.ajax({
            url: kua_calculator_vars.ajax_url,
            type: 'POST',
            data: {
                action: 'get_kua_products',
                kua_number: kuaNumber,
                nonce: kua_calculator_vars.nonce
            },
            success: function(response) {
                if (response.success && response.data.products.length > 0) {
                    // Display products in a grid
                    var productsHtml = '<div class="kua-product-grid">';
                    
                    $.each(response.data.products, function(index, product) {
                        productsHtml += '<div class="kua-product-item">';
                        
                        if (product.image) {
                            productsHtml += '<img class="kua-product-image" src="' + product.image + '" alt="' + product.name + '">';
                        }
                        
                        productsHtml += '<div class="kua-product-info">';
                        productsHtml += '<h4 class="kua-product-title">' + product.name + '</h4>';
                        productsHtml += '<div class="kua-product-price">' + product.price_html + '</div>';
                        productsHtml += '<a href="' + product.url + '" class="kua-product-link">' + kua_calculator_vars.view_product_text + '</a>';
                        productsHtml += '</div></div>';
                    });
                    
                    productsHtml += '</div>';
                    $('#kua-products-list').html(productsHtml);
                    $('#kua-product-recommendations').show();
                } else {
                    // No products found
                    $('#kua-products-list').html('');
                    $('#kua-product-recommendations').hide();
                }
            },
            error: function() {
                // Error fetching products
                $('#kua-products-list').html('<p>' + kua_calculator_vars.error_text + '</p>');
                $('#kua-product-recommendations').hide();
            }
        });
    }
    
    // Set date input to Lithuanian format
    if (kua_calculator_vars.locale === 'lt_LT') {
        document.getElementById('kua-birth-date').lang = 'lt';
    }
    
    // Handle calculate button click
    $('#kua-calculate-button').on('click', function() {
        // Get form values
        const birthDateStr = $('#kua-birth-date').val();
        const gender = $('input[name="gender"]:checked').val();
        
        // Validate input
        if (!birthDateStr || !gender) {
            $('#kua-error-message').text(kua_calculator_vars.error_incomplete);
            $('#kua-error').fadeIn();
            $('#kua-result').hide();
            return;
        }
        
        // Parse birth date
        const birthDate = new Date(birthDateStr);
        
        // Calculate Kua number
        const kuaNumber = calculateKua(birthDate, gender);
        
        if (kuaNumber === 'error') {
            $('#kua-error-message').text(kua_calculator_vars.error_calculation);
            $('#kua-error').fadeIn();
            $('#kua-result').hide();
            return;
        }
        
        // Display results
        $('#kua-number-display').text(kuaNumber);
        $('#kua-description').text(kua_calculator_vars.descriptions[kuaNumber] || '');
        $('#kua-error').hide();
        $('#kua-result').fadeIn();
        
        // Load associated products
        loadKuaProducts(kuaNumber);
        
        // Scroll to result
        $('html, body').animate({
            scrollTop: $('#kua-result').offset().top - 50
        }, 500);
    });
});