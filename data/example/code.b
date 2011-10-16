Program logic:

/**************************************************************************/



1) need handling for \n

2) need to detect EOF: on EOF input is minus 1

3) only one two digit number (10) needs to be processed



/*************************************************************************/



>>      //first two cell are check bits for 0 and \n inputs

, +     //read input and inc it by 1: if EOL: then it becomes 0 hence loop skipped

[           //loop till EOF

    -                           //if not EOL then loop entered: dec value to initial state

    <+>                         // set \n check bit to 1

    ----------                  // dec value by 10: if \n (ASCII value =10): then value becomes 0: loop skipped

    [

        <-<[-]+>>               //set \n check bit to 0 (not found): and 0 check bit to 1

        ++++++++++              //inc by 10 to initial value

        >++++++++[<------>-]<   // ASCII of 0 =48: hence dec 8*6=48 times to get real value (eg for input 5: ASCII=53: 53 minus 48=5 ) Let real value be n

        [                       // if n is zero then loop is skipped

            <<->>                               //set 0 check bit to 0 (not found)

            

            /***************** Logic for squaring the number***************/

            [>+>+>+<<<-]>>>-                    //copy n to multiple cells

            [<[<+<+>>-]<<[>>+<<-]>>>-]<<        //repetedly add n to itself n times to get its square

            

            /***************** Logic for dividing the answer by 10 to get the two digit of squared number *********/

            >[-]>[-]++++++++++<<

            [>>

              [->+<<<-[>]>>>

                [<

                  [-<+>]

                  >>

                ]

                <<

              ]

              <[>>[->>>+<<<]>>-<<<<[-]]>

              >[-<+>]>>+<<<<<

            ]

            >>>>>

            [>>[-]++++++[-<<++++++++>>]<<.[-]]          //if first digit is not zero: print it

            >

            [>[-]++++++[-<++++++++>]<.[-]]              //if second digit is not zero: print it

            <<<<<<[-]

        ]

        <<[->++++++++[<++++++>-]<..[-]]                 //if 0 check bit is 1: print two zeros : for 100 (special case)

    ]

    <[+++++++++.[-]]>[-]>                               //if \n check bit is 1: print \n

    , +                         //take inputs till EOF

]

[-]++++++++++.                                          //print a ending \n