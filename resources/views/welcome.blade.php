<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mayor's Certification</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            font-size: 11pt;
        }
        .page {
            width: 8.27in; /* A4 width */
            height: 11.69in; /* A4 height */
            box-sizing: border-box;
            position: relative;
        }

        .container {
            display: flex;
            flex-direction: row;

        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header img {
            width: 100px; /* Adjust image size as needed */
            height: auto;
        }
        .content {
            padding-top: 1.25in; /* Top margin for content */
            padding-bottom: 0.1in; /* Bottom margin for content */
            padding-left: 1in; /* Left margin for content */
            padding-right: 0.98in; /* Right margin for content */
        }
        .signature {
            margin-top: 50px;
        }
        .footer {
            position: absolute;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 10pt;
        }
    </style>
</head>
<body>
    <div class="page">

        <!-- Header Section -->
       <div style="display: flex; align-items: center; border-bottom: 2px solid #0066cc; padding-bottom: 10px;">
            <!-- Logo -->
           <img
                width="135px"
                height="135px"
                src="{{ asset('images/cgbLogo.png') }}"
            />


            <!-- Header Text -->
            <div style="flex-grow: 1;">
                <p style="font-size: 10pt; margin: 0;">Republic of the Philippines</p>
                <p style="font-size: 12pt; font-weight: bold; margin: 0;">CITY GOVERNMENT OF BUTUAN</p>
                <p style="font-size: 10pt; margin: 0;">CITY BUSINESS PERMITS AND LICENSING DEPARTMENT</p>
                <p style="font-size: 10pt; margin: 0;">City Hall Bldg., J.P. Rosales Ave., Doongan, Butuan City</p>
            </div>
        </div>

        <!-- Main Content Section -->
        <div class="content">
            <p><strong>TO WHOM IT MAY CONCERN:</strong></p>
            <p>This is to certify that per Court Clearance, <strong>MR. SADAM PINDAT IMAM</strong>, a resident of <strong>Purok 7, Barangay 14 – Limaha, Butuan City</strong>, was the respondent in the complaint, to wit:</p>

            <p><strong>Civil Case No. 13126</strong> entitled <strong>Hrs. of Apronio Sarvida, et. al. VS. Rogelia Burdeos Trello, Sadam Imam, et. al.</strong> for “<strong>Accion De Revindicacion</strong>” filed on <strong>August 19, 2022</strong> and raffled to <strong>MTCC-Branch 1, Butuan City</strong>, which is still pending in Court.</p>

            <p>This certification is issued upon the request of <strong>MR. SADAM PINDAT IMAM</strong> for:</p>
            <p><strong>LOCAL EMPLOYMENT</strong></p>

            <p>Done this 8th day of April 2024 at the City Hall Building, Butuan City, Philippines.</p>

            <div class="signature">
                <p><strong>ENGR. RONNIE VICENTE C. LAGNADA</strong><br>
                City Mayor</p>

                <p>For and by authority of the City Mayor:</p>
                <p><strong>ATTY. MOSHI ARIEL S. CAHOY</strong><br>
                City Government Department Head II</p>
            </div>
        </div>

        <!-- Footer Section -->
        <div style="margin-top: 50px; border-top: 2px solid #0066cc; padding-top: 10px; font-size: 10pt;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <!-- Left Section -->
                <div style="line-height: 1.6;">
                    <p style="margin: 0; text-align: left;">O.R. No.: <span style="font-weight: bold;">2595873</span></p>
                    <p style="margin: 0; text-align: left;">Date issued: <span style="font-weight: bold;">04/08/2024</span></p>
                    <p style="margin: 0; text-align: left;">Amount Paid: <span style="font-weight: bold;">Php100.00</span></p>
                    <p style="margin: 0; text-align: left; font-size: 8pt; color: gray;">
                        04/03/2024 03:28 PM by: Ria Blanzche L. Petallar
                    </p>
                </div>

                <!-- Right Section -->
                <div>
                    <img
                        height = "89px";
                        width = "213px";
                        src="{{ asset('images/butuanOnLogo.png') }}"
                    />
                </div>
            </div>

            <!-- Footer Text -->
            <div style="text-align: center; margin-top: 10px;">
                <p style="margin: 0; font-style: italic; color: green;">“Fast, Efficient and Friendly Service”</p>
                <p style="margin: 0; font-weight: bold; color: red;">
                    Note: This permit is valid until December 31, 2024 only.
                </p>
                <p style="margin: 0; font-size: 8pt; color: gray;">CBPLO.BPLD.C.002.REV02</p>
            </div>
        </div>

    </div>
</body>
</html>
